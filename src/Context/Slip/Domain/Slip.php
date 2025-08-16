<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\Slip\Domain\ValueObject\SlipAmount;
use App\Context\Slip\Domain\ValueObject\SlipDueDate;
use App\Context\Slip\Domain\ValueObject\SlipId;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;

class Slip extends AggregateRoot
{
    private readonly DateTimeImmutable $createdAt;

    private ?DateTimeImmutable $paidAt = null;

    private SlipStatus $status;

    private function __construct(
        private readonly SlipId $id,
        private readonly int $amount,
        private readonly ResidentUnit $residentUnit,
        private readonly DateTimeImmutable $dueDate,
        private readonly ?string $description = null,
    ) {
        $this->status = SlipStatus::PENDING;
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * @throws \DateMalformedStringException
     */
    public static function createForUnit(
        SlipId $id,
        SlipAmount $amount,
        ResidentUnit $residentUnit,
        SlipDueDate $dueDate,
        ?string $description = 'Cuota de mantenimiento'
    ): self
    {
        return new self(
            $id,
            $amount->value(),
            $residentUnit,
            $dueDate->toDateTimeImmutable(),
            $description
        );
    }

    public function id(): SlipId
    {
        return $this->id;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function residentUnit(): ResidentUnit
    {
        return $this->residentUnit;
    }

    public function status(): SlipStatus
    {
        return $this->status;
    }

    public function dueDate(): DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function paidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function markAsPaid(): void
    {
        $this->status = SlipStatus::PAID;
        $this->paidAt = new DateTimeImmutable();
    }

    public function markAsOverdue(): void
    {
        $this->status = SlipStatus::OVERDUE;
    }

    public function markAsCancelled(): void
    {
        $this->status = SlipStatus::CANCELLED;
    }
}