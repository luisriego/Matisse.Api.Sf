<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Domain\ValueObject;

use App\Context\Gas\Domain\ValueObject\GasAmount;
use Random\RandomException;

final class GasAmountMother
{
    public static function create(?int $value = null): GasAmount
    {
        // A default, sensible value for tests (e.g., R$540.00)
        return new GasAmount($value ?? 54000);
    }

    /**
     * @throws RandomException
     */
    public static function random(): GasAmount
    {
        // Generate a random amount between R$50 and R$1000 for more varied tests
        return self::create(random_int(5000, 100000));
    }
}