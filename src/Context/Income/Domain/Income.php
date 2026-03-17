<?php

declare(strict_types=1);

namespace App\Context\Income\Domain;

use App\Context\Income\Domain\Event\IncomeDescriptionWasChanged;
use App\Context\Income\Domain\Event\IncomeDueDateWasChanged;
use App\Context\Income\Domain\Event\IncomeWasCategorized;
use App\Context\Income\Domain\Event\IncomeWasEntered;
use App\Context\Income\Domain\Event\IncomeWasPaid;
use App\Context\Income\Domain\ValueObject\IncomeAmount;
use App\Context\Income\Domain\ValueObject\IncomeDueDate;
use App\Context\Income\Domain\ValueObject\IncomeId;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Shared\Domain\AggregateRoot;
use DateMalformedStringException;
use DateTime;
use DateTimeImmutable;

class Income extends AggregateRoot
{
    private readonly DateTimeImmutable $createdAt;
    private bool $isActive;
    private ?DateTimeImmutable $paidAt = null;

    public function __construct(
        private readonly string $id,
        private int $amount,
        private ResidentUnit $residentUnit,
        private ?IncomeType $incomeType,
        private DateTime $dueDate,
        private ?string $description = null,
    ) {
        $this->createdAt = new DateTimeImmutable();
        $this->isActive = true;
    }

    public static function create(
        IncomeId $id,
        IncomeAmount $amount,
        ResidentUnit $residentUnit,
        IncomeType $type,
        string $accountId, // Added accountId
        IncomeDueDate $dueDate,
        ?string $description = null,
    ): self {
        $income =  new self(
            $id->value(),
            $amount->value(),
            $residentUnit,
            $type,
            $dueDate->toDateTime(),
            $description,
        );

        $income->record(new IncomeWasEntered(
            aggregateId: $id->value(),
            amount: $amount->value(),
            residentUnitId: $residentUnit->id(),
            type: $type->id(),
            accountId: $accountId, // Pass accountId to the event
            dueDate: $dueDate->toDateTime()->format('Y-m-d'),
            description: $description,
        ));

        return $income;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function incomeType(): ?IncomeType
    {
        return $this->incomeType;
    }

    public function dueDate(): DateTime
    {
        return $this->dueDate;
    }

    public function paidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function markAsPaid(): void
    {
        if (null === $this->paidAt) {
            $this->paidAt = new DateTimeImmutable();
            $this->record(new IncomeWasPaid($this->id()));
        }
    }

    public function residentUnit(): ResidentUnit
    {
        return $this->residentUnit;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function categorizeAs(?IncomeType $incomeType): void
    {
        $this->incomeType = $incomeType;

        if ($incomeType !== null) {
            $this->record(new IncomeWasCategorized($this->id(), $incomeType->id()));
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    public function changeDueDate(DateTime $dueDate): void
    {
        $this->dueDate = $dueDate;
        $this->record(new IncomeDueDateWasChanged(
            $this->id(),
            $this->dueDate->format('Y-m-d'),
        ));
    }

    /**
     * @throws DateMalformedStringException
     */
    public function changeDescription(string $description): void
    {
        $this->description = $description;
        $this->record(new IncomeDescriptionWasChanged(
            $this->id(),
            $this->description,
        ));
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'type' => $this->incomeType?->toArray(),
            'dueDate' => $this->dueDate->format('Y-m-d'),
            'paidAt' => $this->paidAt?->format('Y-m-d'),
            'residentUnitId' => $this->residentUnit?->id(),
            'description' => $this->description,
        ];
    }
}
