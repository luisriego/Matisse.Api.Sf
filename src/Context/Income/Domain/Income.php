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

    // Nuevas propiedades para el desglose de montos
    private readonly int $mainAccountAmount;
    private readonly int $gasAmount;
    private readonly int $reserveFundAmount;
    private readonly int $constructionFundAmount;

    public function __construct(
        private readonly string $id,
        private int $amount,
        private ResidentUnit $residentUnit,
        private ?IncomeType $incomeType,
        private DateTime $dueDate,
        int $mainAccountAmount, // Nuevo parámetro
        int $gasAmount, // Nuevo parámetro
        int $reserveFundAmount, // Nuevo parámetro
        int $constructionFundAmount, // Nuevo parámetro
        private ?string $description = null,
    ) {
        $this->createdAt = new DateTimeImmutable();
        $this->isActive = true;
        $this->mainAccountAmount = $mainAccountAmount; // Asignar
        $this->gasAmount = $gasAmount; // Asignar
        $this->reserveFundAmount = $reserveFundAmount; // Asignar
        $this->constructionFundAmount = $constructionFundAmount; // Asignar
    }

    public static function create(
        IncomeId $id,
        IncomeAmount $amount,
        ResidentUnit $residentUnit,
        IncomeType $type,
        IncomeDueDate $dueDate,
        int $mainAccountAmount, // Nuevo parámetro
        int $gasAmount, // Nuevo parámetro
        int $reserveFundAmount, // Nuevo parámetro
        int $constructionFundAmount, // Nuevo parámetro
        ?string $description = null,
    ): self {
        $income =  new self(
            $id->value(),
            $amount->value(),
            $residentUnit,
            $type,
            $dueDate->toDateTime(),
            $mainAccountAmount, // Pasar al constructor
            $gasAmount, // Pasar al constructor
            $reserveFundAmount, // Pasar al constructor
            $constructionFundAmount, // Pasar al constructor
            $description,
        );

        $income->record(new IncomeWasEntered(
            aggregateId: $id->value(),
            amount: $amount->value(),
            residentUnitId: $residentUnit->id(),
            type: $type->id(),
            dueDate: $dueDate->toDateTime()->format('Y-m-d'),
            description: $description,
            mainAccountAmount: $mainAccountAmount, // Pasar al evento
            gasAmount: $gasAmount, // Pasar al evento
            reserveFundAmount: $reserveFundAmount, // Pasar al evento
            constructionFundAmount: $constructionFundAmount, // Pasar al evento
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

    // Nuevos getters para las propiedades de desglose
    public function mainAccountAmount(): int
    {
        return $this->mainAccountAmount;
    }

    public function gasAmount(): int
    {
        return $this->gasAmount;
    }

    public function reserveFundAmount(): int
    {
        return $this->reserveFundAmount;
    }

    public function constructionFundAmount(): int
    {
        return $this->constructionFundAmount;
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
            'mainAccountAmount' => $this->mainAccountAmount,
            'gasAmount' => $this->gasAmount,
            'reserveFundAmount' => $this->reserveFundAmount,
            'constructionFundAmount' => $this->constructionFundAmount,
        ];
    }
}
