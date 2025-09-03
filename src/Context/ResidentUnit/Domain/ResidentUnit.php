<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Domain;

use App\Shared\Domain\AggregateRoot;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class ResidentUnit extends AggregateRoot
{
    private string $id;
    private string $unit;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private DateTime $updatedAt;
    private float $idealFraction = 0.0;
    private array $notificationRecipients = [];
    private Collection $incomes;
    private Collection $slips;

    private function __construct(string $id, string $unit, float $idealFraction)
    {
        $this->id = $id;
        $this->unit = $unit;
        $this->idealFraction = $idealFraction;
        $this->incomes = new ArrayCollection();
        $this->slips = new ArrayCollection();
    }

    public static function create(
        ResidentUnitId $id,
        ResidentUnitVO $unit,
        ResidentUnitIdealFraction $idealFraction,
    ): self {
        $residentUnit = new self($id->value(), $unit->value(), $idealFraction->value());
        $residentUnit->isActive = true;
        $residentUnit->createdAt = new DateTimeImmutable();
        $residentUnit->markAsUpdated();

        return $residentUnit;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function unit(): string
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

    public function notificationRecipients(): array
    {
        return $this->notificationRecipients;
    }

    public function setNotificationRecipients(array $recipients): void
    {
        $this->notificationRecipients = $recipients;
        $this->markAsUpdated();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'unit' => $this->unit,
            'idealFraction' => $this->idealFraction,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'notificationRecipients' => $this->notificationRecipients,
        ];
    }
}
