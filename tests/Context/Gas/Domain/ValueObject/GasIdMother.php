<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Domain\ValueObject;

use App\Context\Gas\Domain\ValueObject\GasId;

final class GasIdMother
{
    public static function create(?string $value = null): GasId
    {
        return new GasId($value ?? GasId::random()->value());
    }

    public static function random(): GasId
    {
        return self::create();
    }
}