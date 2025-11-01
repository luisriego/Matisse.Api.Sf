<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Domain;

use App\Context\Gas\Domain\Gas;
use App\Context\Gas\Domain\ValueObject\BufferPercentage;
use App\Context\Gas\Domain\ValueObject\CylinderCapacity;
use App\Context\Gas\Domain\ValueObject\GasAmount;
use App\Tests\Context\Gas\Domain\ValueObject\BufferPercentageMother;
use App\Tests\Context\Gas\Domain\ValueObject\CylinderCapacityMother;
use App\Tests\Context\Gas\Domain\ValueObject\GasAmountMother;

final class GasMother
{
    public static function create(
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

    public static function random(): Gas
    {
        return self::create(
            GasAmountMother::random(),
            CylinderCapacityMother::random(),
            BufferPercentageMother::random()
        );
    }
}