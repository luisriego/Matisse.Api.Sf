<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\RemoveRecurringExpense;

use App\Shared\Application\Command;

final readonly class RemoveRecurringExpenseCommand implements Command
{
    public function __construct(private string $id) {}

    public function id(): string
    {
        return $this->id;
    }
}
