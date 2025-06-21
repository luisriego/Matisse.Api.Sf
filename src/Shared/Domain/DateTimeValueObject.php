<?php

namespace App\Shared\Domain;

class DateTimeValueObject
{
    public function __construct(protected \DateTime $value) {}

    final public function value(): \DateTime
    {
        return  $this->value;
    }
}
