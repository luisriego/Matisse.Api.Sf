<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Domain\ValueObject;

use App\Context\Gas\Domain\ValueObject\GasAmount;

final class GasAmountMother
{
    public static function create(?int $value = null): GasAmount
    {
        return new GasAmount($value ?? 10000); // Usar un int, por ejemplo 10000 para representar 100.00
    }
}
