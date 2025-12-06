<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class AIServiceUnavailableException extends Exception
{
    public function __construct(string $message = 'AI service is currently unavailable.', int $code = Response::HTTP_SERVICE_UNAVAILABLE, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
