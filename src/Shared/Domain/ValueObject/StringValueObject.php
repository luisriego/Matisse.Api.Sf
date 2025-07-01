<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use function mb_strlen;

readonly abstract class StringValueObject
{
    public function __construct(protected string $value) {}

    final public function value(): string
    {
        return $this->value;
    }

    final public function isEmpty(): bool
    {
        return empty($this->value);
    }

    final public function length(): int
    {
        return mb_strlen($this->value);
    }
}
