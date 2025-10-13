<?php

declare(strict_types=1);

namespace App\Context\Income\Domain;

interface IncomeTypeRepository
{
    public function save(IncomeType $incomeType, bool $flush = true): void;

    public function findOneByIdOrFail(string $id): IncomeType;
}
