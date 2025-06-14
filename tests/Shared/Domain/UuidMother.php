<?php

namespace App\Tests\Shared\Domain;

use Symfony\Component\Uid\Uuid;

final class UuidMother
{
    public static function create(): string
    {
        return Uuid::v4()->toRfc4122();
    }
}