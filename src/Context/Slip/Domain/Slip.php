<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\Slip\Domain\Event\SlipWasCancelled;
use App\Context\Slip\Domain\Event\SlipWasImported;
use App\Context\Slip\Domain\Event\SlipWasMarkedAsOverdue;
use App\Context\Slip\Domain\Event\SlipWasPaid;
use App\Context\Slip\Domain\Event\SlipWasSubmitted;
use App\Context\Slip\Domain\ValueObject\SlipAmount;
use App\Context\Slip\Domain\ValueObject\SlipDueDate;
use App\Context\Slip\Domain\ValueObject\SlipId;
use App\Context\Slip\Domain\ValueObject\SlipOrigin;
use App\Shared\Domain\AggregateRoot;
use DateMalformedStringException;
use DateTimeImmutable;

use const DATE_ATOM;

class Slip extends AggregateRoot
{
    private readonly DateTimeImmutable $createdAt;

    private ?DateTimeImmutable $paidAt = null;

    private SlipStatus $status;

    private SlipOrigin $origin;

    private function __construct(
        private readonly string $id,
        private readonly int $amount,
        private readonly ResidentUnit $residentUnit,
        private readonly DateTimeImmutable $dueDate,
        private readonly ?string $description = null,
    ) {
        $this->status = SlipStatus::PENDING;
        $this->origin = SlipOrigin::GENERATED;
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function createForUnit(
        SlipId $id,
        SlipAmount $amount,
        ResidentUnit $residentUnit,
        SlipDueDate $dueDate,
        ?string $description = 'Cuota de mantenimiento',
    ): self {
        return new self(
            $id->value(),
            $amount->value(),
            $residentUnit,
            $dueDate->toDateTimeImmutable(),
            $description,
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function importForUnit(
        SlipId $id,
        SlipAmount $amount,
        ResidentUnit $residentUnit,
        SlipDueDate $dueDate,
        ?string $description = 'Importação histórica',
    ): self {
        $slip = new self(
            $id->value(),
            $amount->value(),
            $residentUnit,
            $dueDate->toDateTimeImmutable(),
            $description,
        );
        $slip->status = SlipStatus::PAID;
        $slip->origin = SlipOrigin::IMPORTED;
        $slip->paidAt = $slip->createdAt;
        $slip->record(
            new SlipWasImported(
                $slip->id,
                $residentUnit->id(),
                $amount->value(),
                $dueDate->toDateTimeImmutable()->format(DATE_ATOM),
            ),
        );

        return $slip;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function getStatus(): string
    {
        return $this->status->value;
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

    public function origin(): SlipOrigin
    {
        return $this->origin;
    }

    public function residentUnit(): ResidentUnit
    {
        return $this->residentUnit;
    }

    public function markAsSubmitted(): void
    {
        $this->status = SlipStatus::SUBMITTED;
        $this->record(
            new SlipWasSubmitted(
                $this->id,
                $this->residentUnit->id(),
                $this->amount,
                $this->dueDate->format(DATE_ATOM),
            ),
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    public function markAsPaid(): void
    {
        $this->status = SlipStatus::PAID;
        $this->paidAt = new DateTimeImmutable();
        $this->record(new SlipWasPaid($this->id));
    }

    /**
     * @throws DateMalformedStringException
     */
    public function markAsOverdue(): void
    {
        $this->status = SlipStatus::OVERDUE;
        $this->record(new SlipWasMarkedAsOverdue($this->id));
    }

    /**
     * @throws DateMalformedStringException
     */
    public function markAsCancelled(): void
    {
        $this->status = SlipStatus::CANCELLED;
        $this->record(new SlipWasCancelled($this->id));
    }

    /**
     * This method should only be used by the Symfony Workflow component.
     */
    public function setStatus(string $status): void
    {
        $this->status = SlipStatus::from($status);
    }
}
