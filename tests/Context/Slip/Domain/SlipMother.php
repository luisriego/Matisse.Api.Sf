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
        int $mainAccountAmount = 0, // Nuevo parámetro con valor por defecto
        int $gasAmount = 0, // Nuevo parámetro con valor por defecto
        int $reserveFundAmount = 0, // Nuevo parámetro con valor por defecto
        int $constructionFundAmount = 0, // Nuevo parámetro con valor por defecto
    ): Slip {
        return Slip::createForUnit(
            $id ?? SlipIdMother::create(),
            $amount ?? SlipAmountMother::create(),
            $residentUnit ?? ResidentUnitMother::create(),
            $dueDate ?? SlipDueDateMother::create(),
            $mainAccountAmount, // Pasar al método
            $gasAmount, // Pasar al método
            $reserveFundAmount, // Pasar al método
            $constructionFundAmount, // Pasar al método
            $description ?? 'Concepto de prueba',
        );
    }
}
