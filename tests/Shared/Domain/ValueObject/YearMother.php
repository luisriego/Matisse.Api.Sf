<?php

declare(strict_types=1);

namespace App\Tests\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Year;

final class YearMother
{
    public static function create(?int $value = null): Year
    {
        return new Year($value ?? 2024);
    }
}
