<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain;

use DateTimeImmutable;

/**
 * Parameters used when slips were generated for an expense month (extra/reserve per unit).
 * Used to replay the same slip breakdown when splitting boleto settlement income across accounts.
 */
class SlipGenerationParameterSnapshot
{
    private string $id;

    private int $expenseYear;

    private int $expenseMonth;

    private int $extraFeePerUnitCents;

    private int $reserveFundPerUnitCents;

    private DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        int $expenseYear,
        int $expenseMonth,
        int $extraFeePerUnitCents,
        int $reserveFundPerUnitCents,
    ) {
        $this->id = $id;
        $this->expenseYear = $expenseYear;
        $this->expenseMonth = $expenseMonth;
        $this->extraFeePerUnitCents = $extraFeePerUnitCents;
        $this->reserveFundPerUnitCents = $reserveFundPerUnitCents;
        $this->createdAt = new DateTimeImmutable();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function expenseYear(): int
    {
        return $this->expenseYear;
    }

    public function expenseMonth(): int
    {
        return $this->expenseMonth;
    }

    public function extraFeePerUnitCents(): int
    {
        return $this->extraFeePerUnitCents;
    }

    public function reserveFundPerUnitCents(): int
    {
        return $this->reserveFundPerUnitCents;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updateFees(int $extraFeePerUnitCents, int $reserveFundPerUnitCents): void
    {
        $this->extraFeePerUnitCents = $extraFeePerUnitCents;
        $this->reserveFundPerUnitCents = $reserveFundPerUnitCents;
        $this->createdAt = new DateTimeImmutable();
    }
}
