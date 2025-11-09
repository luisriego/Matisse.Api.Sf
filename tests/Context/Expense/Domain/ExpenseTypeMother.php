<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\ExpenseType;

final class ExpenseTypeMother
{
    public static function create(
        ?string $id = null,
        ?string $name = null,
        ?string $description = null
    ): ExpenseType {
        return new ExpenseType(
            $id ?? 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            $name ?? 'Type', // Shorter name
            $description ?? 'Default Type Description'
        );
    }
}
