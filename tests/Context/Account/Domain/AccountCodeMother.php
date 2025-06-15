<?php

namespace App\Tests\Context\Account\Domain;

use App\Context\Account\Domain\AccountCode;
use App\Tests\Shared\Domain\MotherCreator;
use App\Tests\Shared\Domain\WordMother;

class AccountCodeMother
{
    public static function create(?string $value = null): AccountCode
    {
        if (null !== $value) {
            return new AccountCode($value);
        }

        $faker = MotherCreator::random();

        // First 3 characters must be letters
        $letters = $faker->regexify('[A-Z]{3}');

        // Next 2 characters must be numbers
        $numbers = $faker->regexify('[0-9]{2}');

        // Remaining characters (0-5) can be letters or numbers
        $remainingLength = $faker->numberBetween(0, 5);
        $remaining = $remainingLength > 0 ? $faker->regexify('[A-Z0-9]{'.$remainingLength.'}') : '';

        $code = $letters . $numbers . $remaining;

        return new AccountCode($code);
    }

    public static function createWithMaxLength(): AccountCode
    {
        $faker = MotherCreator::random();
        $letters = $faker->regexify('[A-Z]{3}');
        $numbers = $faker->regexify('[0-9]{2}');
        $remaining = $faker->regexify('[A-Z0-9]{5}');

        return new AccountCode($letters . $numbers . $remaining);
    }
}