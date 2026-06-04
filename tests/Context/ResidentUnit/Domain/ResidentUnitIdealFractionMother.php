<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Domain;

use App\Context\ResidentUnit\Domain\ResidentUnitIdealFraction;

use function mt_rand;
use function round;

final class ResidentUnitIdealFractionMother
{
    public static function create(?float $value = null): ResidentUnitIdealFraction
    {
        return new ResidentUnitIdealFraction($value ?? self::random());
    }

    private static function random(): float
    {
        return round(mt_rand(10, 100) / 100, 2);
    }
}
