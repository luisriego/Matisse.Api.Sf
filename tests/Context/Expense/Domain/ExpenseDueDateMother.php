<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\ValueObject\ExpenseDueDate;
use DateTime;

final class ExpenseDueDateMother
{
    public static function create(?string $value = null): ExpenseDueDate
    {
        // Creates a date in 'Y-m-d' format.
        // You can use a library like Faker for more random values.
        return new ExpenseDueDate(new DateTime($value ?? 'now'));
    }
}
