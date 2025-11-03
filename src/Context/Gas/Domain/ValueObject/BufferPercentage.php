<?php

declare(strict_types=1);

namespace App\Context\Gas\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\ValueObject\IntValueObject;

final class BufferPercentage extends IntValueObject
{
    public function __construct(int $value)
    {
        $this->ensureIsWithinRange($value);
        parent::__construct($value);
    }

    public function toFactor(): float
    {
        return $this->value / 100;
    }

    private function ensureIsWithinRange(int $value): void
    {
        if ($value < 0 || $value > 100) {
            throw new InvalidArgumentException('BufferPercentage must be between 0 and 100.');
        }
    }
}
