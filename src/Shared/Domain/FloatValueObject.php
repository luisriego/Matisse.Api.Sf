<?php

declare(strict_types=1);

namespace App\Shared\Domain;

class FloatValueObject
{
    public function __construct(protected float $value) {}

    final public function value(): float
    {
        return $this->value;
    }
}
