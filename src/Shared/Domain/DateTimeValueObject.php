<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use DateTime;

class DateTimeValueObject
{
    public function __construct(protected DateTime $value) {}

    public function value(): string
    {
        return $this->value->format('Y-m-d');
    }

    public function toDateTime(): DateTime
    {
        return $this->value;
    }
}
