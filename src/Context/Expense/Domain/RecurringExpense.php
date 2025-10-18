<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain;

use App\Context\Expense\Domain\Bus\RecurringExpenseWasCreated;
use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDay;
use App\Context\Expense\Domain\ValueObject\ExpenseEndDate;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Context\Expense\Domain\ValueObject\ExpenseStartDate;
use App\Shared\Domain\AggregateRoot;
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
    private bool $hasPredefinedAmount;
    private ?string $accountId = null; // Made nullable with default

    public function __construct(
        private readonly string $id,
        ?string $accountId, // Made nullable
        private int $amount,
        private ExpenseType $expenseType,
        private int $dueDay,
        private array $monthsOfYear,
        private DateTimeInterface $startDate,
        private ?DateTimeInterface $endDate = null,
        private ?string $description = null,
        private ?string $notes = null,
        bool $hasPredefinedAmount = true
    ) {
        $this->accountId = $accountId;
        // Doctrine will manage this collection
        $this->createdAt  = new DateTimeImmutable();
        $this->isActive   = true;
        $this->hasPredefinedAmount = $hasPredefinedAmount;
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function create(
        ExpenseId $id,
        string $accountId, // Still required for new entities
        ExpenseAmount $amount,
        ExpenseType $expenseType,
        ExpenseDueDay $dueDay,
        array $monthsOfYear,
        ?ExpenseStartDate $startDate = null,
        ?ExpenseEndDate $endDate   = null,
        ?string $description      = null,
        ?string $notes            = null,
        bool $hasPredefinedAmount = true
    ): self {
        $start = $startDate ?? ExpenseStartDate::from();
        $end = $endDate   ?? ExpenseEndDate::from();

        $recurringExpense = new self(
            $id->value(),
            $accountId,
            $amount->value(),
            $expenseType,
            $dueDay->value(),
            $monthsOfYear,
            $start->toDateTime(),
            $end->toDateTime(),
            $description,
            $notes,
            $hasPredefinedAmount
        );

        $recurringExpense->record(new RecurringExpenseWasCreated(
            $id->value(),
            $accountId,
            $amount->value(),
            $expenseType->id(),
            $dueDay->value(),
            $monthsOfYear,
            $start->toDateTime()->format('Y-m-d H:i:s'),
            $end->toDateTime()?->format('Y-m-d H:i:s'),
            $description,
            $notes,
            null // Pass null for eventId
        ));

        return $recurringExpense;
    }

    // getters...
    public function id(): string
    {
        return $this->id;
    }

    public function accountId(): ?string
    {
        return $this->accountId;
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

    public function hasPredefinedAmount(): bool
    {
        return $this->hasPredefinedAmount;
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
}
