<?php

namespace App\Enums;

use MyCLabs\Enum\Enum;

/**
 * @method static self MANUAL()
 * @method static self INACTIVITY_THRESHOLD()
 */
final class RevocationReason extends Enum {
    private const MANUAL = 'manual';
    private const INACTIVITY_THRESHOLD = 'inactivity_threshold';
}   