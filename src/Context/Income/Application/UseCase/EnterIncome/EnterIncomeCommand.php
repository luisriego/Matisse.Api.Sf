<?php

declare(strict_types=1);

namespace App\Context\Income\Application\UseCase\EnterIncome;

use App\Shared\Application\Command;

final readonly class EnterIncomeCommand implements Command
{
    public function __construct(
        private string $id,
        private int $amount,
        private string $residentUnitId,
        private string $type,
        private string $dueDate,
        private ?bool $isActive,
        private ?string $description = null,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function residentUnitId(): string
    {
        return $this->residentUnitId;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function dueDate(): string
    {
        return $this->dueDate;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function description(): ?string
    {
        return $this->description;
    }
}
