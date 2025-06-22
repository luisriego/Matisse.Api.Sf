<?php

namespace App\Context\Expense\Application\UseCase\PayedAtExpense;

use App\Shared\Application\Command;

final readonly class PayedAtExpenseCommand implements Command
{

    /**
     * @param string $id
     */
    public function __construct(private string $id) {}

    public function id(): string
    {
        return $this->id;

    }
}