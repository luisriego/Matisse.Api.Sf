<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\UpdateRecurringExpense;

use App\Shared\Application\Command;

readonly class UpdateRecurrentExpenseCommand implements Command
{
    public function __construct(
        private string $id,
        private ?int $amount,
        private ?string $type,
        private ?int $dueDay,
        private ?array $monthsOfYear,
        private ?string $startDate,
        private ?string $endDate,
        private ?string $description,
        private ?string $notes,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function amount(): ?int
    {
        return $this->amount;
    }

    public function type(): ?string
    {
        return $this->type;
    }

    public function dueDay(): ?int
    {
        return $this->dueDay;
    }

    public function monthsOfYear(): ?array
    {
        return $this->monthsOfYear;
    }

    public function startDate(): ?string
    {
        return $this->startDate;
    }

    public function endDate(): ?string
    {
        return $this->endDate;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }
}
