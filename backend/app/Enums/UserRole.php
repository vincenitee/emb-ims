<?php

namespace App\Enums;

use MyCLabs\Enum\Enum;

/**
 * @method static self ADMIN()
 * @method static self SUPERADMIN()
 */
final class UserRole extends Enum
{
    private const ADMIN = 'admin';
    private const SUPERADMIN = 'superadmin';
}
