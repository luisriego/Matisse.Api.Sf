<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain;

use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDay;
use App\Context\Expense\Domain\ValueObject\ExpenseEndDate;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Context\Expense\Domain\ValueObject\ExpenseStartDate;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\Exception\InvalidDataException;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class RecurringExpense extends AggregateRoot
{
    private Collection $expenses;
    private readonly DateTimeImmutable $createdAt;
    private bool $isActive;

    public function __construct(
        private readonly string $id,
        private int $amount,
        private ExpenseType $expenseType,
        private int $dueDay,
        private array $monthsOfYear,
        private DateTimeInterface $startDate,
        private ?DateTimeInterface $endDate = null,
        private ?string $description = null,
        private ?string $notes = null,
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

        self::ensureStartDateIsNotInThePast($start);

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

    public function type(): ExpenseType
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

    public function updateAmount(?int $amount): void
    {
        if (null !== $amount) {
            $this->amount = $amount;
        }
    }

    public function updateType(ExpenseType $type): void
    {
        $this->expenseType = $type;
    }

    public function UpdateDueDay(?int $dueDay): void
    {
        if (null !== $dueDay) {
            $this->dueDay = $dueDay;
        }
    }

    public function updateMonthsOfYear(?array $monthsOfYear): void
    {
        if (null !== $monthsOfYear) {
            $this->monthsOfYear = $monthsOfYear;
        }
    }

    public function updateStartDate(?DateTimeInterface $startDate): void
    {
        if (null !== $startDate) {
            $this->startDate = $startDate;
        }
    }

    public function updateEndDate(?DateTimeInterface $endDate): void
    {
        if (null !== $endDate) {
            $this->endDate = $endDate;
        }
    }

    public function updateDescription(?string $description): void
    {
        if (null !== $description) {
            $this->description = $description;
        }
    }

    public function updateNotes(?string $notes): void
    {
        if (null !== $notes) {
            $this->notes = $notes;
        }
    }

    private static function ensureStartDateIsNotInThePast(ExpenseStartDate $startDate): void
    {
        $now = new DateTimeImmutable('today');

        if ($startDate->toDateTime() < $now) {
            throw InvalidDataException::because('O gasto recurrente não pode começar no passado');
        }
    }
}
