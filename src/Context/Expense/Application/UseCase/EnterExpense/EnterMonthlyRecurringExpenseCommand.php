<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\EnterExpense;

use App\Shared\Application\Command;

final readonly class EnterMonthlyRecurringExpenseCommand implements Command
{
    public function __construct(
        private string $expenseId,
        private string $recurringExpenseId,
        private string $accountId,
        private int $amount,
        private string $date, // Changed from DateTimeImmutable to string
    ) {}

    public function expenseId(): string
    {
        return $this->expenseId;
    }

    public function recurringExpenseId(): string
    {
        return $this->recurringExpenseId;
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function date(): string // Changed return type to string
    {
        return $this->date;
    }
}
