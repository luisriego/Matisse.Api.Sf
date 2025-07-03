<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\ExpenseTypeName;

final class ExpenseTypeNameMother
{
    private const NAMES = [
        'General Expenses',
        'Travel',
        'Accommodation',
        'Dining',
        'Office Supplies',
        'Subscriptions',
        'Marketing',
        'Training',
        'Maintenance',
        'Utilities',
        'Insurance',
    ];

    public static function create(?string $name = null): ExpenseTypeName
    {
        $value = $name ?? self::randomName();
        return new ExpenseTypeName($value);
    }

    private static function randomName(): string
    {
        return self::NAMES[array_rand(self::NAMES)];
    }
}
