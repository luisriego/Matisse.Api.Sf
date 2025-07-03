<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\ExpenseTypeId;
use Symfony\Component\Uid\Uuid;

class ExpenseTypeIdMother
{
    public static function create(?string $value = null): ExpenseTypeId
    {
        return new ExpenseTypeId($value ?? Uuid::v4()->toRfc4122());
    }
}