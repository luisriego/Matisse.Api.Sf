<?php

declare(strict_types=1);

namespace App\Context\Condominium\Domain;

use App\Context\Condominium\Domain\Bus\ConstructionFundAmountSet;
use App\Context\Condominium\Domain\Bus\ReserveFundAmountSet;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\Uuid;
use DateTimeImmutable;

class CondominiumConfiguration extends AggregateRoot
{
    private string $id;
    private int $reserveFundAmount;
    private int $constructionFundAmount;
    private DateTimeImmutable $effectiveDate;
    private ?string $userId;

    public function __construct(
        string $id,
        int $reserveFundAmount,
        int $constructionFundAmount,
        DateTimeImmutable $effectiveDate,
        ?string $userId = null
    ) {
        $this->id = $id;
        $this->reserveFundAmount = $reserveFundAmount;
        $this->constructionFundAmount = $constructionFundAmount;
        $this->effectiveDate = $effectiveDate;
        $this->userId = $userId;
    }

    public static function create(
        Uuid $id,
        int $reserveFundAmount,
        int $constructionFundAmount,
        DateTimeImmutable $effectiveDate,
        ?Uuid $userId = null
    ): self {
        $instance = new self(
            $id->value(),
            $reserveFundAmount,
            $constructionFundAmount,
            $effectiveDate,
            $userId?->value()
        );

        return $instance;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function reserveFundAmount(): int
    {
        return $this->reserveFundAmount;
    }

    public function constructionFundAmount(): int
    {
        return $this->constructionFundAmount;
    }

    public function effectiveDate(): DateTimeImmutable
    {
        return $this->effectiveDate;
    }

    public function userId(): ?string
    {
        return $this->userId;
    }

    public function updateAmounts(
        int $newReserveFundAmount,
        int $newConstructionFundAmount,
        DateTimeImmutable $newEffectiveDate,
        ?string $userId = null
    ): void {
        if ($this->reserveFundAmount !== $newReserveFundAmount) {
            $this->record(new ReserveFundAmountSet(
                $this->id,
                $newReserveFundAmount,
                $newEffectiveDate->format('Y-m-d'),
                $userId
            ));
            $this->reserveFundAmount = $newReserveFundAmount;
        }

        // Registrar evento solo si el monto de obra ha cambiado
        if ($this->constructionFundAmount !== $newConstructionFundAmount) {
            $this->record(new ConstructionFundAmountSet(
                $this->id,
                $newConstructionFundAmount,
                $newEffectiveDate->format('Y-m-d'),
                $userId
            ));
            $this->constructionFundAmount = $newConstructionFundAmount;
        }

        // Actualizar la fecha efectiva y el usuario si han cambiado
        if ($this->effectiveDate !== $newEffectiveDate) {
            $this->effectiveDate = $newEffectiveDate;
        }
        if ($this->userId !== $userId) {
            $this->userId = $userId;
        }
    }
}
