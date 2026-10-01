<?php

namespace App\Controllers\Api;

use App\Enums\ActivityAction;
use App\Enums\UserRole;
use App\Enums\UserTypes;
use App\Exceptions\AccessDeniedException;
use App\Exceptions\InvalidCredentialsException;
use App\Libraries\ActivityLogService;
use App\Libraries\AdminAuthService;
use App\Models\NativeAdminModel;
use Config\Services;
use Exception;

class AdminAuthController extends BaseApiController
{
    private AdminAuthService $adminAuthService;
    private ActivityLogService $activityLogService;
    private NativeAdminModel $nativeAdminModel;

    public function __construct()
    {
        $this->adminAuthService = Services::adminAuthService();
        $this->activityLogService = Services::activityLogService();
        $this->nativeAdminModel = new NativeAdminModel();
    }

    public function login()
    {
        $body = $this->request->getJSON(true);

        if (!$this->validate([
            'username' => 'required',
            'password' => 'required'
        ])) {
            return $this->fail($this->validator->getErrors(), 400);
        }

        try {
            $response = $this->adminAuthService->login($body['username'], $body['password']);

            session()->regenerate();
            session()->set([
                'isLoggedIn' => true,
                'user_type' => UserTypes::SYSTEM()->getValue(),
                'admin_id' => $response['id'],
                'username' => $response['username'],
                'role' => (new UserRole($response['role']))->getValue(),
            ]);

            return $this->respond([
                'message' => 'Login successful.',
                'user'    => $response,
            ]);
        } catch (InvalidCredentialsException $e) {
            log_message('notice', "Admin login failed (invalid_credentials) for username: {$body['username']}");
            return $this->fail('Invalid credentials.', 422);
        } catch (AccessDeniedException $e) {
            log_message('error', "Admin login failed (access_denied) for username: {$body['username']}");
            return $this->failForbidden('Access denied. Please contact your system administrator if you believe this was an error');
        } catch (Exception $e) {
            log_message('error', 'Admin login failed (unknown_error): ' . $e->getMessage() . ' ' . $e->getTraceAsString());
            return $this->fail('An unexpected error occured. Please try again later.', 500);
        }
    }

    /**
     * Manual, superadmin-only password reset for another admin's account.
     * Deliberately not a self-service "email me a reset link" flow -- see
     * the routes file for why. Gated to the 'superadmin' role entirely by
     * the 'auth:superadmin' filter on this route, not by anything in here.
     */
    public function resetAdminPassword()
    {
        $body = $this->request->getJSON(true);

        if (!$this->validate([
            'username'     => 'required',
            'new_password' => 'required|min_length[8]',
        ])) {
            return $this->fail($this->validator->getErrors(), 400);
        }

        $targetAdmin = $this->nativeAdminModel->findAdminByUsername($body['username']);

        if (!$targetAdmin) {
            return $this->failNotFound('No admin account matches that username.');
        }

        $updated = $this->nativeAdminModel->update($targetAdmin->id, [
            'password' => password_hash($body['new_password'], PASSWORD_DEFAULT),
        ]);

        if (!$updated) {
            log_message('error', "Password reset failed for admin '{$targetAdmin->username}', attempted by '" . session()->get('username') . "'.");
            return $this->fail('Failed to reset password. Please try again later.', 500);
        }

        log_message('notice', "Password reset for admin '{$targetAdmin->username}' performed by '" . session()->get('username') . "'.");

        // Same isolation pattern as AuthController::login() -- a failed
        // audit-log write must never be mistaken for the reset itself
        // failing, since the password change already succeeded above.
        try {
            $isLogged = $this->activityLogService->logActivity(
                session()->get('admin_id'),
                UserTypes::SYSTEM(),
                ActivityAction::ADMIN_PASSWORD_RESET(),
                "Reset password for admin '{$targetAdmin->username}'."
            );

            if (!$isLogged) {
                log_message('error', "Activity log write failed for admin password reset (target: {$targetAdmin->username}).");
            }
        } catch (\Throwable $e) {
            log_message('error', 'Activity log write threw for admin password reset: ' . $e->getMessage());
        }

        return $this->respond(['message' => "Password for '{$targetAdmin->username}' has been reset."]);
    }

    public function me()
    {
        // Deliberately trusting the session here, not re-querying the DB:
        // the 'adminAuth' filter on this route already re-validated
        // is_active and refreshed the role for THIS request before this
        // method ever ran. Re-checking again here would just repeat that
        // work.
        return $this->respond([
            'admin_id' => session()->get('admin_id'),
            'username' => session()->get('username'),
            'role'     => session()->get('role'),
        ]);
    }

    public function logout()
    {
        // Session data is the source of truth here, not a $response from
        // some other call — logout has no such thing, and the whole reason
        // this endpoint is reachable is that a session already exists. Must
        // read it before destroy() below, since it's gone after.
        $adminId  = session()->get('admin_id');
        $username = session()->get('username');

        if ($adminId !== null) {
            try {
                $isLogged = $this->activityLogService->logActivity(
                    $adminId,
                    UserTypes::SYSTEM(),
                    ActivityAction::LOGOUT(),
                    "Admin {$username} logged out."
                );

                if (!$isLogged) {
                    log_message('error', "Activity log write failed for admin logout by {$username}");
                }
            } catch (\Throwable $e) {
                log_message('error', "Activity log write threw for admin logout by {$username}: " . $e->getMessage());
            }
        }

        session()->destroy();

        return $this->respond(['message' => 'Successfully logged out.']);
    }
}
