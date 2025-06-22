<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\ExpenseDueDate;

final class ExpenseDueDateMother
{
    public static function create(?\DateTimeImmutable $value = null): ExpenseDueDate
    {
        return new ExpenseDueDate($value ?? new \DateTime('now'));
    }
}