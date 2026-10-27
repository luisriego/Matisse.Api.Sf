<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

use Exception;

/**
 * Represents a transient/fatal error that may succeed on retry
 * (e.g., network timeout, temporary service unavailability).
 */
class TransientException extends Exception
{
}
