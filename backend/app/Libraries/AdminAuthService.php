<?php

namespace App\Libraries;

use App\Exceptions\AccessDeniedException;
use App\Exceptions\InvalidCredentialsException;
use App\Models\NativeAdminModel;

class AdminAuthService
{
    protected NativeAdminModel $nativeAdminModel;

    public function __construct()
    {
        $this->nativeAdminModel = new NativeAdminModel();
    }

    public function login(string $username, string $password): array
    {
        $adminUserEntity = $this->nativeAdminModel->findAdminByUsername($username);

        if (!$adminUserEntity || !password_verify($password, $adminUserEntity->getPasswordHash())) {
            log_message('error', "Failed login attempt for username: {$username}");
            throw new InvalidCredentialsException();
        }

        $adminUser = $adminUserEntity->toArray();

        if(!$adminUser['is_active']) {
            log_message('error', "Login denied for username: {$username}");
            throw new AccessDeniedException();
        }

        return [
            'id' => $adminUser['id'],
            'username' => $adminUser['username'],
            'role' => $adminUser['role'],
            'is_active' => $adminUser['is_active'],
        ];
    }

    
}
