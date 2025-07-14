<?php

declare(strict_types=1);

namespace App\Context\Income\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\ValueObject\DateTimeValueObject;
use DateTime;

class IncomePaidAt extends DateTimeValueObject
{
    public function __construct(DateTime $value)
    {
        $this->ensureIsFutureDate($value);
        parent::__construct($value);
    }

    public static function fromDateTime(DateTime $paidAt): self
    {
        return new self($paidAt);
    }

    private function ensureIsFutureDate(DateTime $date): void
    {
        $now = new DateTime();

        if ($date <= $now) {
            throw new InvalidArgumentException();
        }
    }
}
