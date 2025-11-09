<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\ValueObject\ExpenseAmount;

final class ExpenseAmountMother
{
    public static function create(?int $value = null): ExpenseAmount
    {
        // You can use a library like Faker for more random values
        return new ExpenseAmount($value ?? 10000); // Default to 100.00
    }
}
