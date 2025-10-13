<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Domain;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Tests\Shared\Domain\UuidMother;

final class ResidentUnitMother
{
    public static function create(?string $id = null, ?string $unit = null): ResidentUnit
    {
        return new ResidentUnit(
            $id ?? UuidMother::create(),
            $unit ?? 'Unit-A'
        );
    }
}
