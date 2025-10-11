<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain;

interface ExpenseTypeRepository
{
    /**
     * @return ExpenseType[]
     */
    public function findAll(): array;

    public function findOneByIdOrFail(string $id): ExpenseType;
}
