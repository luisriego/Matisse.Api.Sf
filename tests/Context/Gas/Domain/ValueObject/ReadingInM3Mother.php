<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Domain\ValueObject;

use App\Context\Gas\Domain\ValueObject\ReadingInM3;

final class ReadingInM3Mother
{
    public static function create(?float $value = null): ReadingInM3
    {
        return new ReadingInM3($value ?? 1234.56);
    }
}
