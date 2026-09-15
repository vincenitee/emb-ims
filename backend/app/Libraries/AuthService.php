<?php

namespace App\Libraries;

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

    public function login (string $username, string $password): array 
    {
        return [];
    }
}
