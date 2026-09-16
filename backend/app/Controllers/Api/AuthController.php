<?php

namespace App\Controllers\Api;

use App\Enums\UserTypes;
use App\Exceptions\AccessDeniedException;
use App\Exceptions\InvalidCredentialsException;
use App\Libraries\AuthService;
use Exception;

class AuthController extends BaseApiController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function login()
    {
        // Fetch the body from the form response
        $body = $this->request->getJSON(true);

        if (!$this->validate([
            'username' => 'required',
            'password' => 'required',
        ])) {
            return $this->fail($this->validator->getErrors(), 400);
        }

        try {
            $response = $this->authService->login(
                $body['username'],
                $body['password']
            );

            session()->regenerate();
            session()->set([
                'isLoggedIn'     => true,
                'user_type'      => UserTypes::CARHRIS()->getValue(),
                'carhris_emp_id' => $response['carhris_emp_id'],
                'username'       => $response['username'],
                'full_name'      => $response['full_name'],
                'division_id'    => $response['division_id'],
                'division_name'  => $response['division_name'],
                'roles'          => $response['roles'],
            ]);
        } catch (InvalidCredentialsException $e) {
            log_message('notice', "Login failed (invalid_credentials): $e");
            return $this->fail('Invalid Credentials', 422);
        } catch (AccessDeniedException $e) {
            log_message('notice', "Login failed (access_denied): $e");
            return $this->failForbidden('Access Denied', 403);
        } catch (Exception $e) {
            log_message('notice', "Login failed (unknown_error): $e");
            return $this->fail("Unknown error occured: $e");
        }
    }

    public function me() {}

    public function logout() {}
}
