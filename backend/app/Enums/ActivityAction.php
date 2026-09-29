<?php

namespace App\Enums;

use MyCLabs\Enum\Enum;

/**
 * activity_logs.action is a plain VARCHAR(50), not a DB ENUM — deliberately,
 * since the action list is expected to keep growing (document routing,
 * approvals, etc., still to be defined). Add new actions here as they're
 * needed; that's a code change only, no migration required. Validated
 * against this list in ActivityLogModel before insert.
 *
 * @method static self LOGIN()
 * @method static self LOGOUT()
 * @method static self ADMIN_PASSWORD_RESET()
 */
final class ActivityAction extends Enum
{
    private const LOGIN = 'login';
    private const LOGOUT = 'logout';
    private const ADMIN_PASSWORD_RESET = 'admin_password_reset';
}
