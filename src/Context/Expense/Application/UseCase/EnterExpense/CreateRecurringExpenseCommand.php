<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\EnterExpense;

use App\Shared\Application\Command;

final readonly class CreateRecurringExpenseCommand implements Command
{
    public function __construct(
        private string $id,
        private int $amount,
        private string $type,
        private string $accountId,
        private int $dueDay,
        private array $monthsOfYear,
        private string $startDate,
        private string $endDate,
        private string $description,
        private string $notes,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function dueDay(): int
    {
        return $this->dueDay;
    }

    public function monthsOfYear(): array
    {
        return $this->monthsOfYear;
    }

    public function startDate(): string
    {
        return $this->startDate;
    }

    public function endDate(): string
    {
        return $this->endDate;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function notes(): string
    {
        return $this->notes;
    }
}
