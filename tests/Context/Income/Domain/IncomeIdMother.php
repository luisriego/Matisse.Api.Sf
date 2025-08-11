<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Domain;

use App\Context\Income\Domain\ValueObject\IncomeId;
use App\Tests\Shared\Domain\UuidMother;

final class IncomeIdMother
{
    public static function create(?string $value = null): IncomeId
    {
        if (null !== $value) {
            return new IncomeId($value);
        }

        return new IncomeId(UuidMother::create());
    }
}
