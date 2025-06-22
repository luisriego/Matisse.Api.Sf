<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\ExpenseAmount;

final class ExpenseAmountMother
{
    public static function create(?int $value = null): ExpenseAmount
    {
        return new ExpenseAmount($value ?? 10000);
    }
}