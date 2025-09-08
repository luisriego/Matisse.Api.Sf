<?php

declare(strict_types=1);

namespace App\Context\Income\Domain;

use App\Context\Income\Domain\Bus\IncomeWasEntered;
use App\Context\Income\Domain\ValueObject\IncomeAmount;
use App\Context\Income\Domain\ValueObject\IncomeDueDate;
use App\Context\Income\Domain\ValueObject\IncomeId;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Shared\Domain\AggregateRoot;
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

    public function setIncomeType(?IncomeType $incomeType): void
    {
        $this->incomeType = $incomeType;
    }

    public function updateDueDate(DateTime $dueDate): void
    {
        $this->dueDate = $dueDate;
    }

    public function updateDescription(string $description): void
    {
        $this->description = $description;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'dueDate' => $this->dueDate,
            'paidAt' => $this->paidAt,
            'residentUnit' => $this->residentUnit?->unit(),
            'description' => $this->description,
        ];
    }
}
