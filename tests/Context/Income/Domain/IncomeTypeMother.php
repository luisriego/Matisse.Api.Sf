<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Domain;

use App\Context\Income\Domain\IncomeType;
use App\Tests\Shared\Domain\UuidMother;

final class IncomeTypeMother
{
    public static function create(?string $id = null, ?string $name = null): IncomeType
    {
        return new IncomeType(
            $id ?? UuidMother::create(),
            $name ?? 'Salary Income'
        );
    }
}
