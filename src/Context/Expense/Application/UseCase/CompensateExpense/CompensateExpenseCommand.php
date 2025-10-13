<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\CompensateExpense;

use App\Shared\Application\Command;

readonly class CompensateExpenseCommand implements Command
{
    public function __construct(private string $id, private int $amount) {}

    public function id(): string
    {
        return $this->id;
    }

    public function amount(): int
    {
        return $this->amount;
    }
}
