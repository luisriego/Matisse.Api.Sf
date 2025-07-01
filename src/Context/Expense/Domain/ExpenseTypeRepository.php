<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain;

interface ExpenseTypeRepository
{
    public function save(ExpenseType $type, bool $flush = true): void;
    public function findOneByIdOrFail(string $id): ExpenseType;
}