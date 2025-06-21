<?php

namespace App\Context\ResidentUnit\Domain;

use App\Shared\Domain\AggregateRoot;
use DateTime;
use DateTimeImmutable;

class ResidentUnit extends AggregateRoot
{
    private string $id;

    private string $unit;

    private bool $isActive;

    private DateTimeImmutable $createdAt;

    private DateTime $updatedAt;

    private float $idealFraction = 0.0;

//    private Collection $users;
//
//    private Collection $incomes;

    public function __construct(string $id, string $unit, float $idealFraction)
    {
        $this->id = $id;
        $this->unit = $unit;
        $this->idealFraction = $idealFraction;
        $this->isActive = true;
        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(
        ResidentUnitId $id,
        ResidentUnitVO $unit,
        ResidentUnitIdealFraction $idealFraction
    ):self {
        return new self($id->value(), $unit->value(), $idealFraction->value());
    }

    public function id(): string
    {
        return $this->id;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function idealFraction(): float
    {
        return $this->idealFraction;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function markAsUpdated(): void
    {
        $this->updatedAt = new DateTime();
    }

    public function idealFractionMustNotBeMoreThan1(float $accumulatedIF, float $presentValue): bool
    {
        return $accumulatedIF + $presentValue <= 1;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'unit' => $this->unit,
            'idealFraction' => $this->idealFraction,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}