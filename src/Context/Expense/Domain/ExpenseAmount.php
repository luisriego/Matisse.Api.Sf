<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain;

use App\Shared\Domain\IntegerValueObject;
use App\Shared\Domain\InvalidArgumentException;

class ExpenseAmount extends IntegerValueObject
{
    public function __construct(int $value)
    {
        $this->ensureIsPositive($value);

        parent::__construct($value);
    }

    private function ensureIsPositive(int $value): void
    {
        if ($value < 0) {
            throw new InvalidArgumentException("Expense amount must be zero or greater. Got: {$value}");
        }
    }
}
