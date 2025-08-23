<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Domain;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\Slip\Domain\Slip;
use App\Context\Slip\Domain\ValueObject\SlipAmount;
use App\Context\Slip\Domain\ValueObject\SlipDueDate;
use App\Context\Slip\Domain\ValueObject\SlipId;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Context\Slip\Domain\ValueObject\SlipAmountMother;
use App\Tests\Context\Slip\Domain\ValueObject\SlipDueDateMother;
use App\Tests\Context\Slip\Domain\ValueObject\SlipIdMother;
use DateMalformedStringException;

final class SlipMother
{
    /**
     * @throws DateMalformedStringException
     */
    public static function create(
        ?SlipId $id = null,
        ?SlipAmount $amount = null,
        ?ResidentUnit $residentUnit = null,
        ?SlipDueDate $dueDate = null,
        ?string $description = null,
    ): Slip {
        return Slip::createForUnit(
            $id ?? SlipIdMother::create(),
            $amount ?? SlipAmountMother::create(),
            $residentUnit ?? ResidentUnitMother::create(),
            $dueDate ?? SlipDueDateMother::create(),
            $description ?? 'Concepto de prueba',
        );
    }
}
