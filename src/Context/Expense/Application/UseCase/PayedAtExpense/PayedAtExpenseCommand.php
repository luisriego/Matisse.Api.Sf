<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\PayedAtExpense;

use App\Shared\Application\Command;

final readonly class PayedAtExpenseCommand implements Command
{
    public function __construct(private string $id) {}

    public function id(): string
    {
        return $this->id;
    }
}
