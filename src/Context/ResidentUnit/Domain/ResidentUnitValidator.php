<?php

namespace App\Context\ResidentUnit\Domain;

use App\Shared\Domain\InvalidArgumentException;

final class ResidentUnitValidator
{
    private ResidentUnitRepository $repository;

    public function __construct(ResidentUnitRepository $repository)
    {
        $this->repository = $repository;
    }

    public function validateIdealFraction(ResidentUnitIdealFraction $newFraction): void
    {
        $totalFraction = $this->repository->calculateTotalIdealFraction();

        if ($totalFraction + $newFraction->value() > 1.0) {
            throw new InvalidArgumentException('The total of all ideal fractions cannot exceed 1.0.');
        }
    }
}