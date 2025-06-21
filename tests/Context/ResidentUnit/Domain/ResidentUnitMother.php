<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Domain;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitId;
use App\Context\ResidentUnit\Domain\ResidentUnitIdealFraction;
use App\Context\ResidentUnit\Domain\ResidentUnitVO;

final class ResidentUnitMother
{
    public static function create(
        ?ResidentUnitId $id = null,
        ?ResidentUnitVO $unit = null,
        ?ResidentUnitIdealFraction $idealFraction = null
    ): ResidentUnit {
        return ResidentUnit::create(
            $id ?? ResidentUnitIdMother::create(),
            $unit ?? ResidentUnitVOMother::create(),
            $idealFraction ?? ResidentUnitIdealFractionMother::create()
        );
    }
}