<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Domain\ValueObject;

use App\Context\Gas\Domain\ValueObject\BufferPercentage;

final class BufferPercentageMother
{
    public static function create(?int $value = null): BufferPercentage
    {
        return new BufferPercentage($value ?? 10); // Usar un int, por ejemplo 10 para representar 10%
    }
}
