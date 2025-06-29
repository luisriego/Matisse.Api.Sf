<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain;

interface ExpenseRepository
{
    public function flush(): void;
    public function save(Expense $expense, bool $flush = true): void;
    public function remove(Expense $expense, bool $flush = true): void;
    public function findOneByIdOrFail(string $id): Expense;
    public function findAll(): array;
}
