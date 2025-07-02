<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\EnterExpense;

use App\Shared\Application\Command;

final readonly class EnterExpenseWithDescriptionCommand implements Command
{
    public function __construct(
        private string $id,
        private int $amount,
        private string $type,
        private string $accountId,
        private string $dueDate,
        private string $description
    ) {}

    public function id(): string {return $this->id;}
    public function amount(): int {return $this->amount;}
    public function type(): string {return $this->type;}
    public function accountId(): string {return $this->accountId;}
    public function dueDate(): string {return $this->dueDate;}
    public function description(): string {return $this->description;}
}