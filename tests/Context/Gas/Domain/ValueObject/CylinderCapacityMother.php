<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Domain\ValueObject;

use App\Context\Gas\Domain\ValueObject\CylinderCapacity;

final class CylinderCapacityMother
{
    public static function create(?int $value = null): CylinderCapacity
    {
        return new CylinderCapacity($value ?? 45);
    }

    public static function random(): CylinderCapacity
    {
        $commonSizes = [5, 8, 13, 20, 45];
        return self::create($commonSizes[array_rand($commonSizes)]);
    }
}