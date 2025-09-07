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

    private Account $account;

    private ?ExpenseType $type = null;
    private ?RecurringExpense $recurringExpense = null;

    //    #[ORM\ManyToOne(targetEntity: RecurringExpense::class, inversedBy: 'expenses')]
    //    #[ORM\JoinColumn(name: "recurring_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]

    public function __construct(
        string $id,
        int $amount,
        ExpenseType $type,
        ?Account $account,
        DateTime $dueDate,
        ?bool $isActive = true,
        ?string $description = null,
    ) {
        $this->id = $id;
        $this->amount = $amount;
        $this->type = $type;
        $this->account = $account;
        $this->dueDate = $dueDate;
        $this->isActive = $isActive;
        $this->description = $description;
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * @throws \DateMalformedStringException
     */
    public static function create(
        ExpenseId $id,
        ExpenseAmount $amount,
        ExpenseType $type,
        ?Account $account,
        ExpenseDueDate $dueDate,
        ?bool $isActive,
        ?ExpenseDescription $description,
    ): self {
        $expense = new self(
            id: $id->value(),
            amount: $amount->value(),
            type: $type,
            account: $account,
            dueDate: $dueDate->toDateTime(),
            isActive: $isActive,
            description: $description?->value(),
        );

        if ($expense->isActive()) {
            $expense->record(new ExpenseWasEntered(
                $id->value(),
                $amount->value(),
                $type->id(),
                $account->id(),
                $dueDate->value(),
                $description?->value(),
            ));
        }

        return $expense;
    }

    /**
     * @throws \DateMalformedStringException
     */
    public static function createWithDescription(
        ExpenseId $id,
        ExpenseAmount $amount,
        ExpenseType $type,
        ?Account $account,
        ExpenseDueDate $dueDate,
        ExpenseDescription $description,
    ): self {
        $expense = new self($id->value(), $amount->value(), $type, $account, $dueDate->toDateTime());
        $expense->updateDescription($description->value());

        if ($expense->isActive()) {
            $expense->record(new ExpenseWasEntered(
                $id->value(),
                $amount->value(),
                $type->id(),
                $account->id(),
                $dueDate->value(),
            ));
        }

        return $expense;
    }

    // Compensate means that a new instance of Expense replaces an older one.
    public function compensate(): void
    {
        $event = new ExpenseWasCompensated(
            aggregateId: $this->id,
            amount: -$this->amount,
            type: $this->type->id(),
            accountId: $this->account->id(),
            dueDate: $this->dueDate->format('Y-m-d'),
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

    public function type(): ExpenseType
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

    public function account(): Account
    {
        return $this->account;
    }

    public function setAccount(Account $account): void
    {
        $this->account = $account;
    }

    public function markAsPaid(): void
    {
        $this->paidAt = new DateTimeImmutable();
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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'description' => $this->description,
            'dueDate' => $this->dueDate,
            'paidAt' => $this->paidAt,
            'createdAt' => $this->createdAt,
        ];
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
        $this->amount -= $event->toPrimitives()['amount'];
    }
}
