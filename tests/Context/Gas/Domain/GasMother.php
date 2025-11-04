<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Domain;

use App\Context\Gas\Domain\Gas;
use App\Context\Gas\Domain\ValueObject\BufferPercentage;
use App\Context\Gas\Domain\ValueObject\CylinderCapacity;
use App\Context\Gas\Domain\ValueObject\GasAmount;
use App\Context\Gas\Domain\ValueObject\GasId;
use App\Context\Gas\Domain\ValueObject\ReadingInM3;
use App\Context\ResidentUnit\Domain\ResidentUnitId;
use App\Tests\Context\Gas\Domain\ValueObject\BufferPercentageMother;
use App\Tests\Context\Gas\Domain\ValueObject\CylinderCapacityMother;
use App\Tests\Context\Gas\Domain\ValueObject\GasAmountMother;
use App\Tests\Context\Gas\Domain\ValueObject\GasIdMother;
use App\Tests\Context\Gas\Domain\ValueObject\ReadingInM3Mother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitIdMother;
use App\Tests\Shared\Domain\ValueObject\MonthMother;
use App\Tests\Shared\Domain\ValueObject\YearMother;
use App\Shared\Domain\ValueObject\Month;
use App\Shared\Domain\ValueObject\Year;

final class GasMother
{
    public static function createForRecordReading(
        ?GasId $id = null,
        ?ResidentUnitId $residentUnitId = null,
        ?Year $year = null,
        ?Month $month = null,
        ?ReadingInM3 $reading = null
    ): Gas {
        return Gas::recordReading(
            $id ?? GasIdMother::create(),
            $residentUnitId ?? ResidentUnitIdMother::create(),
            $year ?? YearMother::create(),
            $month ?? MonthMother::create(),
            $reading ?? ReadingInM3Mother::create()
        );
    }

    public static function createForDefinePrice(
        ?GasAmount $amount = null,
        ?CylinderCapacity $capacity = null,
        ?BufferPercentage $buffer = null
    ): Gas {
        return Gas::definePrice(
            $amount ?? GasAmountMother::create(),
            $capacity ?? CylinderCapacityMother::create(),
            $buffer ?? BufferPercentageMother::create()
        );
    }
}
