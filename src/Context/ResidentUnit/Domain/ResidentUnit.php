<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Domain;

use App\Context\Income\Domain\Income; // <-- AÑADIDO
use App\Context\Slip\Domain\Slip;     // <-- AÑADIDO
use App\Shared\Domain\AggregateRoot;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection; // <-- AÑADIDO
use Doctrine\Common\Collections\Collection;      // <-- AÑADIDO
use Doctrine\ORM\Mapping as ORM;

                 // <-- AÑADIDO

#[ORM\Entity] // <-- AÑADIDO
#[ORM\Table(name: 'resident_units')] // <-- AÑADIDO
class ResidentUnit extends AggregateRoot
{
    #[ORM\Id] // <-- AÑADIDO
    #[ORM\Column(type: 'string', length: 36)] // <-- AÑADIDO
    private string $id;

    #[ORM\Column(type: 'string', length: 255)] // <-- AÑADIDO
    private string $unit;

    #[ORM\Column(type: 'boolean')] // <-- AÑADIDO
    private bool $isActive;

    #[ORM\Column(type: 'datetime_immutable')] // <-- AÑADIDO
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime')] // <-- AÑADIDO
    private DateTime $updatedAt;

    #[ORM\Column(type: 'float')] // <-- AÑADIDO
    private float $idealFraction = 0.0;

    // --- RELACIONES CORREGIDAS ---
    #[ORM\OneToMany(mappedBy: 'residentUnit', targetEntity: Income::class)] // <-- AÑADIDO
    private Collection $incomes;

    #[ORM\OneToMany(mappedBy: 'residentUnit', targetEntity: Slip::class)] // <-- AÑADIDO
    private Collection $slips;

    public function __construct(string $id, string $unit, float $idealFraction)
    {
        $this->id = $id;
        $this->unit = $unit;
        $this->idealFraction = $idealFraction;
        $this->isActive = true;
        $this->createdAt = new DateTimeImmutable();
        $this->incomes = new ArrayCollection(); // <-- AÑADIDO
        $this->slips = new ArrayCollection();   // <-- AÑADIDO
    }

    public static function create(
        ResidentUnitId $id,
        ResidentUnitVO $unit,
        ResidentUnitIdealFraction $idealFraction,
    ): self {
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
