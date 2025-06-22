<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\EnterExpense;

use App\Shared\Application\Command;

final readonly class EnterExpenseCommand implements Command
{
    public function __construct(
        private string $id,
        private int $amount,
        private string $accountId,
        private string $dueDate,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function dueDate(): string
    {
        return $this->dueDate;
    }
}
