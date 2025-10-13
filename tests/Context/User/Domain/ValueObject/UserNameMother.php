<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Domain\ValueObject;

use App\Context\User\Domain\ValueObject\UserName;
use Faker\Factory;

final class UserNameMother
{
    public static function create(?string $value = null): UserName
    {
        $faker = Factory::create();
        return UserName::fromString($value ?? $faker->name());
    }
}
