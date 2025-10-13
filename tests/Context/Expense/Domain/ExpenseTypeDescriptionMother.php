<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\ValueObject\ExpenseTypeDescription;

final class ExpenseTypeDescriptionMother
{
    private const DESCRIPTIONS = [
        'Standard expense category',
        'Costs related to business travel',
        'Office supplies and equipment',
        'Monthly subscription fees',
        'Training and development expenses',
        'Client entertainment and dining',
        'Maintenance and repairs',
        'Insurance premiums',
        'Utilities and services',
        'Marketing and advertising',
    ];

    public static function create(?ExpenseTypeDescription $description = null): ExpenseTypeDescription
    {
        return $description ?? new ExpenseTypeDescription(self::randomDescription());
    }

    private static function randomDescription(): string
    {
        return self::DESCRIPTIONS[array_rand(self::DESCRIPTIONS)];
    }
}
