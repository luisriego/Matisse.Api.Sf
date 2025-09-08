<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Domain;

use App\Context\Income\Domain\ValueObject\IncomeTypeCode;
use App\Tests\Shared\Domain\MotherCreator;

final class IncomeTypeCodeMother
{
    public static function create(?string $value = null): IncomeTypeCode
    {
        if (null !== $value) {
            return new IncomeTypeCode($value);
        }

        $faker = MotherCreator::random();

        return new IncomeTypeCode($faker->word());
    }
}
