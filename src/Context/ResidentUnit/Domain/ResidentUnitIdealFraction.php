<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Domain;

use App\Shared\Domain\Exception\InvalidArgumentException;

final class ResidentUnitIdealFraction
{
    private float $fraction;

    public function __construct(float $fraction)
    {
        if ($fraction < 0 || $fraction > 1) {
            throw new InvalidArgumentException('A fracao ideal deve ser maior o igual que zero e menor ou igual que um');
        }

        $this->fraction = $fraction;
    }

    public function value(): float
    {
        return $this->fraction;
    }
}
