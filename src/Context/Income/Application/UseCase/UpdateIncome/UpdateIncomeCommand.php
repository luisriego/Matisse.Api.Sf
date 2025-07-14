<?php

declare(strict_types=1);

namespace App\Context\Income\Application\UseCase\UpdateIncome;

use App\Shared\Application\Command;

final readonly class UpdateIncomeCommand implements Command
{
    public function __construct(
        private string $id,
        private ?string $dueDate,
        private ?string $description,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function dueDate(): ?string
    {
        return $this->dueDate;
    }

    public function description(): ?string
    {
        return $this->description;
    }
}
