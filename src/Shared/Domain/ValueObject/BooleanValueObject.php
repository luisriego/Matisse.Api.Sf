<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

class BooleanValueObject
{
    public function __construct(protected bool $value) {}

    final public function value(): bool
    {
        return $this->value;
    }
}
