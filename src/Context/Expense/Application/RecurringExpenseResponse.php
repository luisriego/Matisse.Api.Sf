<?php

declare(strict_types=1);

namespace App\Context\Expense\Application;

final class RecurringExpenseResponse
{
    private string $id;
    private ?string $description;
    private int $amount;
    private int $dueDay;
    private string $startDate;
    private ?string $endDate;
    private ?array $monthsOfYear;
    private bool $isActive;
    private bool $hasPredefinedAmount;

    public function __construct(
        string $id,
        ?string $description,
        int $amount,
        int $dueDay,
        string $startDate,
        ?string $endDate,
        ?array $monthsOfYear,
        bool $isActive,
        bool $hasPredefinedAmount,
    ) {
        $this->id = $id;
        $this->description = $description;
        $this->amount = $amount;
        $this->dueDay = $dueDay;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->monthsOfYear = $monthsOfYear;
        $this->isActive = $isActive;
        $this->hasPredefinedAmount = $hasPredefinedAmount;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function dueDay(): int
    {
        return $this->dueDay;
    }

    public function startDate(): string
    {
        return $this->startDate;
    }

    public function endDate(): ?string
    {
        return $this->endDate;
    }

    public function monthsOfYear(): ?array
    {
        return $this->monthsOfYear;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function hasPredefinedAmount(): bool
    {
        return $this->hasPredefinedAmount;
    }
}
