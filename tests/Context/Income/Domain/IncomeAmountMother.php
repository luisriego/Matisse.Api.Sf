<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Domain;

use App\Context\Income\Domain\ValueObject\IncomeAmount;
use App\Tests\Shared\Domain\MotherCreator;

final class IncomeAmountMother
{
    public static function create(?int $value = null): IncomeAmount
    {
        if (null !== $value) {
            return new IncomeAmount($value);
        }

        return new IncomeAmount(MotherCreator::random()->numberBetween(0, 100000));
    }
}
