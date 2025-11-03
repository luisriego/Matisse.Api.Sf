<?php

declare(strict_types=1);

namespace App\Context\Gas\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\ValueObject\IntValueObject;

final class GasAmount extends IntValueObject
{
    public function __construct(int $value)
    {
        $this->ensureIsGreaterThanZero($value);
        parent::__construct($value);
    }

    public function toFloat(): float
    {
        return $this->value / 100;
    }

    private function ensureIsGreaterThanZero(int $value): void
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('GasBillAmount must be greater than zero.');
        }
    }
}
