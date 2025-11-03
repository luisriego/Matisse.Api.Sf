<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Domain\ValueObject;

use App\Context\Gas\Domain\ValueObject\CylinderCapacity;

final class CylinderCapacityMother
{
    public static function create(?int $value = null): CylinderCapacity
    {
        return new CylinderCapacity($value ?? 100); // Usar un int
    }
}
