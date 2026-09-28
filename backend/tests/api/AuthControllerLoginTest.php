<?php

use App\Exceptions\AccessDeniedException;
use App\Exceptions\InvalidCredentialsException;
use App\Libraries\AuthService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * Covers POST /api/login (AuthController::login).
 *
 * AuthService is mocked at the boundary instead of hitting the real
 * CarhrisUserModel/SystemAccessModel — those read a MySQL view joined
 * against an external `carhris` database, which the SQLite test DB
 * can't stand in for. What's under test here is the controller's own
 * job: input validation, translating AuthService's exceptions into the
 * right HTTP responses, and populating the session correctly on success.
 */
class AuthControllerLoginTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        $this->resetServices();

        parent::tearDown();
    }

    /**
     * Swaps the shared `authService` instance for a mock whose login()
     * method is stubbed, without ever running AuthService's real
     * constructor (which would otherwise instantiate the CARHRIS models).
     */
    private function mockAuthService(): \PHPUnit\Framework\MockObject\MockObject
    {
        $mock = $this->getMockBuilder(AuthService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['login'])
            ->getMock();

        Services::injectMock('authService', $mock);

        return $mock;
    }

    public function testLoginFailsValidationWhenUsernameAndPasswordAreMissing()
    {
        $this->mockAuthService()->expects($this->never())->method('login');

        $result = $this->withBodyFormat('json')->post('api/login', []);

        $result->assertStatus(400);
        $result->assertJSONFragment([
            'messages' => [
                'username' => 'The username field is required.',
                'password' => 'The password field is required.',
            ],
        ]);
    }

    public function testLoginReturns422OnInvalidCredentials()
    {
        $mock = $this->mockAuthService();
        $mock->method('login')->willThrowException(new InvalidCredentialsException());

        $result = $this->withBodyFormat('json')->post('api/login', [
            'username' => 'jdelacruz',
            'password' => 'wrong-password',
        ]);

        $result->assertStatus(422);
        $result->assertJSONFragment(['messages' => ['error' => 'Invalid credentials.']]);
    }

    public function testLoginReturns403WhenAccessIsDenied()
    {
        $mock = $this->mockAuthService();
        $mock->method('login')->willThrowException(new AccessDeniedException());

        $result = $this->withBodyFormat('json')->post('api/login', [
            'username' => 'jdelacruz',
            'password' => 'correct-password',
        ]);

        $result->assertStatus(403);
        $result->assertJSONFragment(['messages' => ['error' => 'Access denied. Please contact your system administrator if you believe this is an error.']]);
    }

    public function testLoginReturns500OnUnexpectedError()
    {
        $mock = $this->mockAuthService();
        $mock->method('login')->willThrowException(new \RuntimeException('DB connection lost'));

        $result = $this->withBodyFormat('json')->post('api/login', [
            'username' => 'jdelacruz',
            'password' => 'correct-password',
        ]);

        $result->assertStatus(500);
        $result->assertJSONFragment(['messages' => ['error' => 'An unexpected error occurred. Please try again later.']]);
    }

    public function testSuccessfulLoginRegeneratesSessionAndSetsExpectedData()
    {
        $mock = $this->mockAuthService();
        $mock->method('login')->willReturn([
            'carhris_emp_id' => 42,
            'username'       => 'jdelacruz',
            'full_name'      => 'Juan Dela Cruz',
            'division_id'    => 3,
            'division_name'  => 'Legal Division',
            'roles'          => [
                'is_division_chief'    => true,
                'is_document_handler'  => false,
                'is_regional_director' => false,
            ],
        ]);

        $result = $this->withBodyFormat('json')->post('api/login', [
            'username' => 'jdelacruz',
            'password' => 'correct-password',
        ]);

        // Not assertOK(): that also requires a non-empty body, and the
        // controller currently sends none on success (see note below).
        $result->assertStatus(200);
        $result->assertSessionHas('isLoggedIn', true);
        $result->assertSessionHas('user_type', 'carhris');
        $result->assertSessionHas('carhris_emp_id', 42);
        $result->assertSessionHas('username', 'jdelacruz');
        $result->assertSessionHas('full_name', 'Juan Dela Cruz');
        $result->assertSessionHas('division_id', 3);
        $result->assertSessionHas('division_name', 'Legal Division');

        // Not assertSessionHas('roles', [...]): that version of the
        // assertion builds its failure message via string interpolation
        // of $value even on success, and blows up on an array (CI4 4.0.5
        // bug — fixed in later versions). Compare directly instead.
        $result->assertSessionHas('roles');
        $this->assertEquals([
            'is_division_chief'    => true,
            'is_document_handler'  => false,
            'is_regional_director' => false,
        ], $_SESSION['roles']);
    }
}
