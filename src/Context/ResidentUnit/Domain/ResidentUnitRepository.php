<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Domain;

interface ResidentUnitRepository
{
    public function save(ResidentUnit $residentUnit, bool $flush = true): void;

    public function findOneByIdOrFail(string $id): ResidentUnit;

    public function findOneById(string $id): ?ResidentUnit;

    public function exists(ResidentUnitId $id): bool; // Added this method

    public function calculateTotalIdealFraction(?string $excludeId = null): float;

    public function findAllActive(): array;
}
