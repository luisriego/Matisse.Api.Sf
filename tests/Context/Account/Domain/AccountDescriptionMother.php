<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Domain;

use App\Context\Account\Domain\AccountDescription;
use App\Tests\Shared\Domain\MotherCreator;

final class AccountDescriptionMother
{
    public static function create(?string $value = null): AccountDescription
    {
        if (null !== $value) {
            return new AccountDescription($value);
        }

        $faker = MotherCreator::random();
        $description = $faker->realText(mt_rand(10, 255));

        return new AccountDescription($description);
    }
}
