<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\UpdateExpense;

use App\Shared\Application\Command;

readonly class UpdateExpenseCommand implements Command
{
    public function __construct(
        private string $id,
        private ?int $amount,
        private ?string $dueDate,
        private ?string $description,
    ) {}

    public function id(): string { return $this->id; }

    public function amount(): ?int { return $this->amount; }


    public function dueDate(): ?string { return $this->dueDate; }

    public function description(): ?string { return $this->description; }
}
