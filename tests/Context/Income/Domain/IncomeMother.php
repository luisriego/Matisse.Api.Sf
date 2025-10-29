<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Domain;

use App\Context\Income\Domain\Income;
use App\Context\Income\Domain\IncomeType;
use App\Context\Income\Domain\ValueObject\IncomeAmount;
use App\Context\Income\Domain\ValueObject\IncomeDueDate;
use App\Context\Income\Domain\ValueObject\IncomeId;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Shared\Domain\ValueObject\Uuid;
use DateTime;

final class IncomeMother
{
    public static function create(
        ?IncomeId $id = null,
        ?IncomeAmount $amount = null,
        ?ResidentUnit $residentUnit = null,
        ?IncomeType $type = null,
        ?IncomeDueDate $dueDate = null,
        ?string $description = null
    ): Income {
        return Income::create(
            $id ?? new IncomeId(Uuid::random()->value()),
            $amount ?? IncomeAmountMother::create(),
            $residentUnit ?? ResidentUnitMother::create(),
            $type ?? IncomeTypeMother::create(),
            $dueDate ?? new IncomeDueDate(new DateTime('+1 day')),
            $description ?? 'Random income description'
        );
    }
}