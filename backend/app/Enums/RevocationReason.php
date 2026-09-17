<?php

namespace App\Enums;

use MyCLabs\Enum\Enum;

final class RevocationReason extends Enum {
    private const MANUAL = 'manual';
    private const INACTIVITY_THRESHOLD = 'inactivity_threshold';
}   