<?php

namespace App\Shared\Domain;

class IntegerValueObject
{
    public function __construct(protected int $value) {}

    final public function value(): int
    {
        return  $this->value;
    }
}
