<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Domain;

use App\Context\ResidentUnit\Domain\ResidentUnitVO;

final class ResidentUnitVOMother
{
    public static function create(?string $value = null): ResidentUnitVO
    {
        return new ResidentUnitVO($value ?? self::random());
    }

    private static function random(): string
    {
        return 'Unit-' . rand(100, 999);
    }
}