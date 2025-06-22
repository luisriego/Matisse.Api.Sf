<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\ExpenseId;
use Symfony\Component\Uid\Uuid;

final class ExpenseIdMother
{
    public static function create(?string $value = null): ExpenseId
    {
        return new ExpenseId($value ?? Uuid::v4()->toRfc4122());
    }
}