<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\AssignAccountTypeToExpenseType;

use App\Shared\Application\Command;

final readonly class AssignAccountTypeToExpenseTypeCommand implements Command
{
    public function __construct(
        private string $expenseTypeId,
        private string $accountTypeId,
    ) {
    }

    public function getExpenseTypeId(): string
    {
        return $this->expenseTypeId;
    }

    public function getAccountTypeId(): string
    {
        return $this->accountTypeId;
    }
}
