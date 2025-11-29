<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain;

interface ExpenseTypeRepository
{
    public function save(ExpenseType $expenseType): void;

    public function findAll(): array;

    public function findOneByIdOrFail(string $id): ExpenseType;

    public function findOneByCodeOrFail(string $code): ExpenseType;
}
