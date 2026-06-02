<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Domain;

use App\Context\Account\Domain\AccountDescription;
use App\Tests\Shared\Domain\MotherCreator;

use function mb_strlen;
use function mt_rand;

final class AccountDescriptionMother
{
    public static function create(?string $value = null): AccountDescription
    {
        if (null !== $value) {
            return new AccountDescription($value);
        }

        $faker = MotherCreator::random();

        do {
            $description = $faker->realText(mt_rand(50, 255));
        } while (mb_strlen($description) < 10);

        return new AccountDescription($description);
    }
}
