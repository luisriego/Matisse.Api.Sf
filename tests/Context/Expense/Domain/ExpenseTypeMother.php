<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\ExpenseType;
use App\Tests\Shared\Domain\UuidMother;

final class ExpenseTypeMother
{
    public static function create(
        ?string $id = null,
        ?string $name = null,
        ?string $description = null
    ): ExpenseType {
        return new ExpenseType(
            $id ?? UuidMother::create(),
            $name ?? 'Type',
            $description ?? 'Default Type Description'
        );
    }
}
