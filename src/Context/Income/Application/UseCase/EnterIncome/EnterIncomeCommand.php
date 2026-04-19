<?php

declare(strict_types=1);

namespace App\Context\Income\Application\UseCase\EnterIncome;

use App\Shared\Application\Command;

final readonly class EnterIncomeCommand implements Command
{
    public function __construct(
        private string $id,
        private int $amount,
        private ?string $residentUnitId,
        private string $type,
        private string $accountId, // Added accountId
        private string $dueDate,
        private ?string $description = null,
        /**
         * Skip the "dueDate must be today or future" invariant.
         * Meant for bank CREDIT lines whose postedAt already happened.
         */
        private bool $allowPastDueDate = false,
        /**
         * If set, the income is created and immediately marked as paid using this date.
         * Meant for bank CREDIT lines (the bank already settled the money).
         */
        private ?string $paidAt = null,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function residentUnitId(): ?string
    {
        return $this->residentUnitId;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function dueDate(): string
    {
        return $this->dueDate;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function allowPastDueDate(): bool
    {
        return $this->allowPastDueDate;
    }

    public function paidAt(): ?string
    {
        return $this->paidAt;
    }
}
