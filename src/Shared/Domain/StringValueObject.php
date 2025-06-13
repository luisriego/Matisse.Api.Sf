<?php

namespace App\Shared\Domain;

abstract class StringValueObject
{
    public function __construct(protected string $value) {}

    final public function value(): string
    {
        return $this->value;
    }
}