<?php

declare(strict_types=1);

namespace App\Context\Gas\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\ValueObject\IntValueObject;

final class CylinderCapacity extends IntValueObject
{
    private const KG_TO_M3_CONVERSION_FACTOR = 0.5;

    public function __construct(int $value)
    {
        $this->ensureIsGreaterThanZero($value);
        parent::__construct($value);
    }



    private function ensureIsGreaterThanZero(int $value): void
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('CylinderCapacity must be greater than zero.');
        }
    }
}
