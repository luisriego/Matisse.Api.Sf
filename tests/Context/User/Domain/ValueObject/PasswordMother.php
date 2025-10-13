<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Domain\ValueObject;

use App\Context\User\Domain\ValueObject\Password;
use Faker\Factory;

final class PasswordMother
{
    public static function create(?string $value = null): Password
    {
        $faker = Factory::create();
        // Generate a password that respects the length constraints (e.g., 8 to 20 chars)
        return Password::fromString($value ?? $faker->password(8, 20));
    }
}
