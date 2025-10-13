<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

class InvalidDataException extends InvalidArgumentException
{
    public static function because(string $message): self
    {
        return new static($message);
    }
}
