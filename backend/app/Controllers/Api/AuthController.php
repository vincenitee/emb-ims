<?php

namespace App\Controllers\Api;

use App\Enums\ActivityAction;
use App\Enums\UserTypes;
use App\Exceptions\AccessDeniedException;
use App\Exceptions\InvalidCredentialsException;
use App\Libraries\ActivityLogService;
use App\Libraries\AuthService;
use Config\Services;
use Exception;

class AuthController extends BaseApiController
{
    private AuthService $authService;
    private ActivityLogService $activityLogService;

    public function __construct()
    {
        $this->authService = Services::authService();
        $this->activityLogService = Services::activityLogService();
    }

    public function login()
    {
        // Fetch the body from the form responsep
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

            try {
                $isLogged = $this->activityLogService->logActivity(
                    $response['carhris_emp_id'],
                    UserTypes::CARHRIS(),
                    ActivityAction::LOGIN(),
                    "User {$response['username']} logged in."
                );

                if (!$isLogged) {
                    log_message('error', "Activity log write failed for login by {$response['username']}");
                }
            } catch (\Throwable $e) {
                log_message('error', "Activity log write threw for login by {$response['username']}: " . $e->getMessage());
            }

            return $this->respond([
                'message' => 'Login successful.',
                'user'    => $response,
            ]);
        } catch (InvalidCredentialsException $e) {
            log_message('notice', 'Login failed (invalid_credentials): ' . $e->getMessage());
            return $this->fail('Invalid credentials.', 422);
        } catch (AccessDeniedException $e) {
            log_message('error', "Login failed (access_denied) for username: {$body['username']}");
            return $this->failForbidden('Access denied. Please contact your system administrator if you believe this is an error.');
        } catch (Exception $e) {
            log_message('error', 'Login failed (unknown_error): ' . $e->getMessage() . ' ' . $e->getTraceAsString());
            return $this->fail('An unexpected error occurred. Please try again later.', 500);
        }
    }

    public function me()
    {
        // Deliberately trusting the session here, not re-querying the DB:
        // the 'auth' filter on this route already re-validated is_active
        // and refreshed the role flags for THIS request before this method
        // ever ran. Re-checking again here would just repeat that work.
        return $this->respond([
            'carhris_emp_id' => session()->get('carhris_emp_id'),
            'username'       => session()->get('username'),
            'full_name'      => session()->get('full_name'),
            'division_id'    => session()->get('division_id'),
            'division_name'  => session()->get('division_name'),
            'roles'          => session()->get('roles'),
        ]);
    }

    public function logout()
    {
        // Session data is the source of truth here, not a $response from
        // some other call — logout has no such thing, and the whole reason
        // this endpoint is reachable is that a session already exists. Must
        // read it before destroy() below, since it's gone after.
        $carhrisEmpId = session()->get('carhris_emp_id');
        $username     = session()->get('username');

        if ($carhrisEmpId !== null) {
            try {
                $isLogged = $this->activityLogService->logActivity(
                    $carhrisEmpId,
                    UserTypes::CARHRIS(),
                    ActivityAction::LOGOUT(),
                    "User {$username} logged out."
                );

                if (!$isLogged) {
                    log_message('error', "Activity log write failed for logout by {$username}");
                }
            } catch (\Throwable $e) {
                log_message('error', "Activity log write threw for logout by {$username}: " . $e->getMessage());
            }
        }

        session()->destroy();

        return $this->respond(['message' => 'Successfully logged out.']);
    }
}
