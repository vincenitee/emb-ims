<?php

namespace App\Libraries;

use App\Enums\RevocationReason;
use App\Exceptions\AccessDeniedException;
use App\Exceptions\InvalidCredentialsException;
use App\Models\CarhrisUserModel;
use App\Models\SystemAccessModel;

class AuthService
{
    protected CarhrisUserModel $carhrisUserModel;
    protected SystemAccessModel $systemAccessModel;

    protected int $inactivityThresholdDays = 30;

    public function __construct()
    {
        $this->carhrisUserModel = new CarhrisUserModel();
        $this->systemAccessModel = new SystemAccessModel();
    }

    public function login(string $username, string $password): array
    {
        // Check against the carhris users
        $carhrisUserEntity = $this->carhrisUserModel->findByUsername($username);

        // getPasswordHash() is the only sanctioned way to read the hash —
        // toArray() deliberately strips it. Checked together so a missing
        // user and a wrong password fail the same way (avoids leaking
        // which case occurred via timing/response differences).
        if (!$carhrisUserEntity || !password_verify($password, $carhrisUserEntity->getPasswordHash())) {
            log_message('notice', "Failed login attempt for username: {$username}");
            throw new InvalidCredentialsException();
        }

        $carhrisUser = $carhrisUserEntity->toArray();
        $carhrisUserId = $carhrisUser['id'];

        // Check for system access
        $accessEntity = $this->systemAccessModel->findByCarhrisId($carhrisUserId);

        if (!$accessEntity) {
            log_message('notice', "Login denied - no system_access for CARHRIS Employee {$carhrisUserId}");
            throw new AccessDeniedException();
        }

        $access = $accessEntity->toArray();

        // Check if the user is active
        if (!$access['is_active']) {
            log_message('notice', "Login denied — inactive access for CARIS employee {$carhrisUserId} (reason: {$access['revocation_reason']})");
            throw new AccessDeniedException();
        }

        // Check the inactivity threshold and revoked the access if invalid
        if ($this->exceedsInactivityThreshold($access['last_signed_in'])) {
            $this->systemAccessModel->update($access['id'], [
                'is_active' => false,
                'revoked_by' => null,
                'revocation_reason' => RevocationReason::INACTIVITY_THRESHOLD()->getValue(),
                'revoked_at' => date('Y-m-d H:i:s'),
            ]);

            throw new AccessDeniedException();
        }

        $this->systemAccessModel->update($access['id'], [
            'last_signed_in' => date('Y-m-d H:i:s'),
        ]);

        // At this point the user is authorized and session will be generated
        return [
            'carhris_emp_id'    => $carhrisUserId,
            'username'          => $carhrisUser['username'],
            'full_name'         => trim("{$carhrisUser['first_name']} {$carhrisUser['middle_name']} {$carhrisUser['last_name']}"),
            'division_id'       => $carhrisUser['division_id'],
            'division_name'     => $carhrisUser['division_name'],
            'roles'             => [
                'is_division_chief'    => (bool) $access['is_division_chief'],
                'is_document_handler'  => (bool) $access['is_document_handler'],
                'is_regional_director' => (bool) $access['is_regional_director'],
            ],
        ];
    }

    protected function exceedsInactivityThreshold(?string $lastSignedIn): bool
    {
        if (!$lastSignedIn) {
            return false;
        }

        $daysSincelastSignedIn = (strtotime('now') - strtotime($lastSignedIn));

        return $daysSincelastSignedIn > $this->inactivityThresholdDays;
    }
}
