<?php

namespace App\Exceptions;

/**
 * Credentials didn't match anything in CARIS.
 * Maps to a generic 401 in the controller — never reveal which part failed.
 */
class InvalidCredentialsException extends \RuntimeException {}
