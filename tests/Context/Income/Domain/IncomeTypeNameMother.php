<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Domain;

use App\Context\Income\Domain\ValueObject\IncomeTypeName;
use App\Tests\Shared\Domain\MotherCreator;

final class IncomeTypeNameMother
{
    public static function create(?string $value = null): IncomeTypeName
    {
        if (null !== $value) {
            return new IncomeTypeName($value);
        }

        $faker = MotherCreator::random();
        // Ensure the name is at least 5 characters long and consists of letters
        $name = $faker->regexify('[A-Za-z]{5,100}');

        return new IncomeTypeName($name);
    }
}
