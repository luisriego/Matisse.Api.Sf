<?php

declare(strict_types=1);

namespace App\Context\Gas\Domain\ValueObject;

use App\Shared\Domain\ValueObject\IntValueObject;
use DomainException;

final class CylinderCapacity extends IntValueObject
{
    private const KG_TO_M3_CONVERSION_FACTOR = 0.5;

    public function __construct(int $value)
    {
        $this->ensureIsGreaterThanZero($value);
        parent::__construct($value);
    }

    public function toM3(): float
    {
        return $this->value * self::KG_TO_M3_CONVERSION_FACTOR;
    }

    private function ensureIsGreaterThanZero(int $value): void
    {
        if ($value <= 0) {
            throw new DomainException('CylinderCapacity must be greater than zero.');
        }
    }
}
