<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use DateTime;
use DateTimeImmutable;

class DateTimeValueObject
{
    protected DateTime $value;

    public function __construct(DateTime|DateTimeImmutable $value)
    {
        if ($value instanceof DateTimeImmutable) {
            $this->value = DateTime::createFromImmutable($value);

            return;
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value->format('Y-m-d');
    }

    public function toDateTime(): DateTime
    {
        return $this->value;
    }
}
