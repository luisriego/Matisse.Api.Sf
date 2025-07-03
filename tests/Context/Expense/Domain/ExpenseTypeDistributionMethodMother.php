<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\ExpenseTypeDistributionMethod;

final class ExpenseTypeDistributionMethodMother
{
    private const METHODS = [
        'equal',
        'fraction',
        'individual',
    ];

    public static function create(?ExpenseTypeDistributionMethod $method = null): ExpenseTypeDistributionMethod
    {
        return $method ?? self::randomMethod();
    }

    private static function randomMethod(): ExpenseTypeDistributionMethod
    {
        $name = self::METHODS[array_rand(self::METHODS)];

        return ExpenseTypeDistributionMethod::$name();
    }
}
