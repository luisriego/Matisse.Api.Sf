<?php

declare(strict_types=1);

namespace App\Tests\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Month;

final class MonthMother
{
    public static function create(?int $value = null): Month
    {
        return new Month($value ?? 1);
    }
}
