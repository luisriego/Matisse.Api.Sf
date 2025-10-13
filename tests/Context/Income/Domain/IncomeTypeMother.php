<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Domain;

use App\Context\Income\Domain\IncomeType;
use App\Context\Income\Domain\ValueObject\IncomeId;
use App\Context\Income\Domain\ValueObject\IncomeTypeCode;
use App\Context\Income\Domain\ValueObject\IncomeTypeName;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Domain\WordMother;

final class IncomeTypeMother
{
    public static function create(
        ?IncomeId $id = null,
        ?IncomeTypeName $name = null,
        ?IncomeTypeCode $code = null,
        ?string $description = null
    ): IncomeType {
        return IncomeType::create(
            $id ?? IncomeIdMother::create(),
            $name ?? IncomeTypeNameMother::create(),
            $code ?? IncomeTypeCodeMother::create(),
            $description ?? WordMother::create(),
        );
    }
}
