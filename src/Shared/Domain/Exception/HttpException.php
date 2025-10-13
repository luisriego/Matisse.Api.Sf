<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

use Exception;

class HttpException extends Exception
{
    public function __construct(private readonly string $statusCode, $message = '', $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): string
    {
        return $this->statusCode;
    }
}
