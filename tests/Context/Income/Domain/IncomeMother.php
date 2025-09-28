<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Domain;

use App\Context\Income\Domain\Income;
use App\Context\Income\Domain\ValueObject\IncomeAmount;
use App\Context\Income\Domain\ValueObject\IncomeDueDate;
use App\Context\Income\Domain\ValueObject\IncomeId;
use App\Context\Income\Domain\IncomeType;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;

final class IncomeMother
{
    public static function create(
        ?IncomeId $id = null,
        ?IncomeAmount $amount = null,
        ?ResidentUnit $residentUnit = null,
        ?IncomeType $type = null,
        ?IncomeDueDate $dueDate = null,
        ?string $description = null,
        int $mainAccountAmount = 0, // Nuevo parámetro con valor por defecto
        int $gasAmount = 0, // Nuevo parámetro con valor por defecto
        int $reserveFundAmount = 0, // Nuevo parámetro con valor por defecto
        int $constructionFundAmount = 0, // Nuevo parámetro con valor por defecto
    ): Income {
        return Income::create(
            $id ?? IncomeIdMother::create(),
            $amount ?? IncomeAmountMother::create(),
            $residentUnit ?? ResidentUnitMother::create(),
            $type ?? IncomeTypeMother::create(),
            $dueDate ?? IncomeDueDateMother::create(),
            $mainAccountAmount, // Pasar al método
            $gasAmount, // Pasar al método
            $reserveFundAmount, // Pasar al método
            $constructionFundAmount, // Pasar al método
            $description
        );
    }
}
