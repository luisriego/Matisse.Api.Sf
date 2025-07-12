<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain;

interface RecurringExpenseRepository
{
    public function flush(): void;

    public function save(RecurringExpense $recurringExpense, bool $flush = true): void;

    public function remove(RecurringExpense $recurringExpense, bool $flush = true): void;

    public function findOneByIdOrFail(string $id): RecurringExpense;

    public function findForThisMonth(int $month): array;
}
