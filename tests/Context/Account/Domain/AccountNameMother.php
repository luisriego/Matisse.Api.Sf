<?php

namespace App\Tests\Context\Account\Domain;

use App\Context\Account\Domain\AccountName;
use App\Tests\Shared\Domain\MotherCreator;
use App\Tests\Shared\Domain\WordMother;

class AccountNameMother
{
    public static function create(?string $value = null): AccountName
    {
        if (null !== $value) {
            return new AccountName($value);
        }

        // Ensure the name is at least 4 characters long
        $name = WordMother::create();
        while (strlen($name) < 4) {
            $name = WordMother::create();
        }

        // Ensure it doesn't exceed 100 characters
        if (strlen($name) > 100) {
            $name = substr($name, 0, 100);
        }

        return new AccountName($name);
    }

    public static function createWithMinLength(): AccountName
    {
        return new AccountName('Test'); // Exactly 4 characters
    }

    public static function createWithMaxLength(): AccountName
    {
        $faker = MotherCreator::random();
        return new AccountName($faker->regexify('[A-Za-z0-9]{100}'));
    }
}