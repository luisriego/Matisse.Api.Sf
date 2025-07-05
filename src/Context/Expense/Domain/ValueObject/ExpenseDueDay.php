<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\ValueObject\IntegerValueObject;

class ExpenseDueDay extends IntegerValueObject
{
    public function __construct(int $value)
    {
        $this->ensureIsMonth($value);

        parent::__construct($value);
    }

    private function ensureIsMonth(int $value): void
    {
        if ($value < 1 || $value > 31) {
            throw new InvalidArgumentException(
                "O dia do vencimento deve ser um número de mês entre 1 e 31: você insertou {$value}"
            );
        }
    }
}