<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Domain\ValueObject;

use App\Context\User\Domain\ValueObject\Email;
use Faker\Factory;

final class EmailMother
{
    public static function create(?string $value = null): Email
    {
        $faker = Factory::create();
        return Email::fromString($value ?? $faker->safeEmail());
    }
}
