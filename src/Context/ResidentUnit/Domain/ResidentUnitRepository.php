<?php

namespace App\Context\ResidentUnit\Domain;

interface ResidentUnitRepository
{
    public function save(ResidentUnit $residentUnit, bool $flush = true): void;
    public function findOneByIdOrFail(string $id): ResidentUnit;
    public function calculateTotalIdealFraction(): float;
}