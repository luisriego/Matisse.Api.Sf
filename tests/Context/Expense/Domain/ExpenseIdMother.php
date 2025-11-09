<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Shared\Domain\ValueObject\Uuid;

final class ExpenseIdMother
{
    public static function create(?string $value = null): ExpenseId
    {
        return new ExpenseId($value ?? Uuid::random()->value());
    }
}
