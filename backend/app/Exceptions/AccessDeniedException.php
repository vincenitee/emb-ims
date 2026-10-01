<?php

namespace App\Exceptions;

/**
 * CARIS auth succeeded, but no system_access row exists, or it's been revoked
 * (manually, or via inactivity auto-revoke). Maps to a generic 403 in the controller —
 * the user-facing message is identical regardless of the underlying reason; only
 * the log line (written inside the service) distinguishes them.
 */
class AccessDeniedException extends \RuntimeException {}
