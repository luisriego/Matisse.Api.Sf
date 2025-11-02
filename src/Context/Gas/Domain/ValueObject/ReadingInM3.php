<?php

declare(strict_types=1);

namespace App\Context\Gas\Domain\ValueObject;

use App\Shared\Domain\ValueObject\FloatValueObject;
use InvalidArgumentException;

final class ReadingInM3 extends FloatValueObject
{
    public function __construct(float $value)
    {
        if ($value < 0) {
            throw new InvalidArgumentException('O consumo não pode ser negativo.');
        }

        parent::__construct($value);
    }
}
