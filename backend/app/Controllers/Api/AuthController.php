<?php

namespace App\Controllers\Api;

use App\Enums\UserTypes;
use App\Exceptions\AccessDeniedException;
use App\Exceptions\InvalidCredentialsException;
use App\Libraries\AuthService;
use Config\Services;
use Exception;

class AuthController extends BaseApiController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = Services::authService();
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
            log_message('error', 'Login failed (invalid_credentials): ' . $e->getMessage());
            return $this->fail('Invalid credentials.', 422);
        } catch (AccessDeniedException $e) {
            log_message('error', 'Login failed (access_denied): ' . $e->getMessage());
            return $this->failForbidden('Access denied.', 403);
        } catch (Exception $e) {
            log_message('error', 'Login failed (unknown_error): ' . $e->getMessage() . ' ' . $e->getTraceAsString());
            return $this->fail('An unexpected error occurred. Please try again later.', 500);
        }
    }

    public function me() {}

    public function logout() {}
}