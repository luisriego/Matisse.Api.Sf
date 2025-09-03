<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\Dto;

use App\Context\Slip\Domain\Slip;

readonly class SlipEmailDto
{
    public function __construct(
        private Slip $slip,
    ) {}

    public function slipId(): string
    {
        return $this->slip->id();
    }

    public function amount(): int
    {
        return $this->slip->amount();
    }

    public function dueDate(): string
    {
        return $this->slip->dueDate()->format('d-m-Y');
    }

    public function monthDueDate(): string
    {
        return $this->slip->dueDate()->format('m');
    }

    public function description(): ?string
    {
        return $this->slip->description();
    }

    public function unitNumber(): string
    {
        return $this->slip->residentUnit()->unit();
    }

    public function recipients(): array
    {
        return $this->slip->residentUnit()->notificationRecipients();
    }

    public function toArray(): array
    {
        return [
            'slip_id' => $this->slipId(),
            'amount' => $this->amount(),
            'due_date' => $this->dueDate(),
            'description' => $this->description(),
            'unit_number' => $this->unitNumber(),
            'recipients' => $this->recipients(),
        ];
    }
}
