<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain;

use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDay;
use App\Context\Expense\Domain\ValueObject\ExpenseEndDate;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Context\Expense\Domain\ValueObject\ExpenseStartDate;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

final class RecurringExpense
{
    private Collection $expenses;
    private readonly DateTimeImmutable $createdAt;
    private bool $isActive;

    public function __construct(
        private readonly string $id,
        private readonly int $amount,
        private readonly ExpenseType $expenseType,
        private readonly int $dueDay,
        private readonly array $monthsOfYear,
        private readonly DateTimeInterface $startDate,
        private readonly ?DateTimeInterface $endDate = null,
        private readonly ?string $description = null,
        private readonly ?string $notes = null,
    ) {
        $this->expenses   = new ArrayCollection();
        $this->createdAt  = new DateTimeImmutable();
        $this->isActive   = true;
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function create(
        ExpenseId $id,
        ExpenseAmount $amount,
        ExpenseType $expenseType,
        ExpenseDueDay $dueDay,
        array $monthsOfYear,
        ?ExpenseStartDate $startDate = null,
        ?ExpenseEndDate $endDate   = null,
        ?string $description      = null,
        ?string $notes            = null,
    ): self {
        $start = $startDate ?? ExpenseStartDate::from();
        $end = $endDate   ?? ExpenseEndDate::from();

        return new self(
            $id->value(),
            $amount->value(),
            $expenseType,
            $dueDay->value(),
            $monthsOfYear,
            $start->toDateTime(),
            $end->toDateTime(),
            $description,
            $notes,
        );
    }

    // getters...
    public function id(): string
    {
        return $this->id;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function expenseType(): ExpenseType
    {
        return $this->expenseType;
    }

    public function dueDay(): int
    {
        return $this->dueDay;
    }

    public function monthsOfYear(): ?array
    {
        return $this->monthsOfYear;
    }

    public function startDate(): DateTimeInterface
    {
        return $this->startDate;
    }

    public function endDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    /**
     * @return Collection<int,Expense>
     */
    public function expenses(): Collection
    {
        return $this->expenses;
    }

    public function addExpense(Expense $e): void
    {
        if (!$this->expenses->contains($e)) {
            $this->expenses->add($e);
            $e->setRecurringExpense($this);
        }
    }

    public function removeExpense(Expense $e): void
    {
        if ($this->expenses->removeElement($e)) {
            $e->setRecurringExpense(null);
        }
    }
}
