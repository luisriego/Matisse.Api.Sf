<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\Slip\Domain\Event\SlipWasSubmitted;
use App\Context\Slip\Domain\ValueObject\SlipAmount;
use App\Context\Slip\Domain\ValueObject\SlipDueDate;
use App\Context\Slip\Domain\ValueObject\SlipId;
use App\Shared\Domain\AggregateRoot;
use DateMalformedStringException;
use DateTimeImmutable;

use const DATE_ATOM;

class Slip extends AggregateRoot
{
    private readonly DateTimeImmutable $createdAt;

    private ?DateTimeImmutable $paidAt = null;

    private SlipStatus $status;

    // Nuevas propiedades para el desglose de montos
    private readonly int $mainAccountAmount;
    private readonly int $gasAmount;
    private readonly int $reserveFundAmount;
    private readonly int $constructionFundAmount;

    private function __construct(
        private readonly string $id,
        private readonly int $amount,
        private readonly ResidentUnit $residentUnit,
        private readonly DateTimeImmutable $dueDate,
        int $mainAccountAmount, // Nuevo parámetro
        int $gasAmount, // Nuevo parámetro
        int $reserveFundAmount, // Nuevo parámetro
        int $constructionFundAmount, // Nuevo parámetro
        private readonly ?string $description = null,
    ) {
        $this->status = SlipStatus::PENDING;
        $this->createdAt = new DateTimeImmutable();
        $this->mainAccountAmount = $mainAccountAmount; // Asignar
        $this->gasAmount = $gasAmount; // Asignar
        $this->reserveFundAmount = $reserveFundAmount; // Asignar
        $this->constructionFundAmount = $constructionFundAmount; // Asignar
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function createForUnit(
        SlipId $id,
        SlipAmount $amount,
        ResidentUnit $residentUnit,
        SlipDueDate $dueDate,
        int $mainAccountAmount, // Nuevo parámetro
        int $gasAmount, // Nuevo parámetro
        int $reserveFundAmount, // Nuevo parámetro
        int $constructionFundAmount, // Nuevo parámetro
        ?string $description = 'Cuota de mantenimiento',
    ): self {
        return new self(
            $id->value(),
            $amount->value(),
            $residentUnit,
            $dueDate->toDateTimeImmutable(),
            $mainAccountAmount, // Pasar al constructor
            $gasAmount, // Pasar al constructor
            $reserveFundAmount, // Pasar al constructor
            $constructionFundAmount, // Pasar al constructor
            $description,
        );
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
                $this->mainAccountAmount, // Incluir en el evento
                $this->gasAmount, // Incluir en el evento
                $this->reserveFundAmount, // Incluir en el evento
                $this->constructionFundAmount, // Incluir en el evento
            ),
        );
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

    /**
     * This method should only be used by the Symfony Workflow component.
     */
    public function setStatus(string $status): void
    {
        $this->status = SlipStatus::from($status);
    }
}
