<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain;

use App\Context\Account\Domain\Account;
use App\Context\Expense\Domain\Bus\ExpenseWasCompensated;
use App\Context\Expense\Domain\Bus\ExpenseWasEntered;
use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseDescription;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDate;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Shared\Domain\AggregateRoot;
use DateMalformedStringException;
use DateTime;
use DateTimeImmutable;

class Expense extends AggregateRoot
{
    private string $id;
    private int $amount;
    private ?string $description = '';
    private DateTime $dueDate;
    private ?DateTimeImmutable $paidAt = null;
    private DateTimeImmutable $createdAt;

    private bool $isActive = true;

    private ?Account $account;
    private ?string $residentUnitId = null; // Added property

    private ?ExpenseType $type = null;
    private ?RecurringExpense $recurringExpense = null;

    public function __construct(
        string $id,
        int $amount,
        ExpenseType $type,
        ?Account $account,
        DateTime $dueDate,
        ?bool $isActive = true,
        ?string $description = null,
        ?string $residentUnitId = null, // Added parameter
    ) {
        $this->id = $id;
        $this->amount = $amount;
        $this->type = $type;
        $this->account = $account;
        $this->dueDate = $dueDate;
        $this->isActive = $isActive;
        $this->description = $description;
        $this->createdAt = new DateTimeImmutable();
        $this->residentUnitId = $residentUnitId; // Assigned property
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function create(
        ExpenseId $id,
        ExpenseAmount $amount,
        ExpenseType $type,
        ?Account $account,
        ExpenseDueDate $dueDate,
        ?bool $isActive,
        ?ExpenseDescription $description,
        ?string $residentUnitId = null,
    ): self {
        $expense = new self(
            id: $id->value(),
            amount: $amount->value(),
            type: $type,
            account: $account,
            dueDate: $dueDate->toDateTime(),
            isActive: $isActive,
            description: $description?->value(),
            residentUnitId: $residentUnitId,
        );

        if ($expense->isActive() && null !== $account) {
            $expense->record(new ExpenseWasEntered(
                $id->value(),
                $amount->value(),
                $type->id(),
                $account->id(),
                $dueDate->value(),
                $description?->value(),
                $residentUnitId, // Passed parameter to event
            ));
        }

        return $expense;
    }

    public function compensate(): void
    {
        if (null === $this->account) {
            return;
        }

        $event = new ExpenseWasCompensated(
            aggregateId: $this->id,
            amount: -$this->amount,
            type: $this->type->id(),
            accountId: $this->account->id(),
            dueDate: $this->dueDate->format('Y-m-d'),
            residentUnitId: $this->residentUnitId, // Passed parameter to event
        );

        $this->record($event);
        $this->applyExpenseWasCompensated($event);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function type(): ?ExpenseType
    {
        return $this->type;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function dueDate(): DateTime
    {
        return $this->dueDate;
    }

    public function paidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function account(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): void
    {
        $this->account = $account;
    }

    public function residentUnitId(): ?string // Added getter
    {
        return $this->residentUnitId;
    }

    public function setResidentUnitId(?string $residentUnitId): void // Added setter
    {
        $this->residentUnitId = $residentUnitId;
    }

    public function markAsPaid(): void
    {
        if (null === $this->paidAt) {
            $this->paidAt = new DateTimeImmutable();
        }
    }

    public function updateAmount(int $amount): void
    {
        if (!$this->paidAt()) {
            $this->amount = $amount;
        }
    }

    public function updateDueDate(DateTime $dueDate): void
    {
        if (!$this->paidAt()) {
            $this->dueDate = $dueDate;
        }
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function activate(bool $isActive): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function updateDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setRecurringExpense(?RecurringExpense $recurringExpense): void
    {
        $this->recurringExpense = $recurringExpense;
    }

    public function recurringExpense(): ?RecurringExpense
    {
        return $this->recurringExpense;
    }

    private function applyExpenseWasCompensated(ExpenseWasCompensated $event): void
    {
        $this->amount += $event->toPrimitives()['amount'];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'description' => $this->description,
            'dueDate' => $this->dueDate,
            'paidAt' => $this->paidAt,
            'createdAt' => $this->createdAt,
            'residentUnitId' => $this->residentUnitId,
            'type' => $this->type?->toArray(),
        ];
    }
}
