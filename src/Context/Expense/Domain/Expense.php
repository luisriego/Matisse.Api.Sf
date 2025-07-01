<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain;

use App\Context\Account\Domain\Account;
use App\Context\Expense\Domain\Bus\ExpenseWasCompensated;
use App\Context\Expense\Domain\Bus\ExpenseWasEntered;
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

    private Account $account;

//    #[ORM\ManyToOne(inversedBy: 'expenses')]
//    private ?ExpenseType $type = null;

    //    #[ORM\ManyToOne(targetEntity: RecurringExpense::class, inversedBy: 'expenses')]
    //    #[ORM\JoinColumn(name: "recurring_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    //    private ?RecurringExpense $recurringExpense = null;

    public function __construct(
        string $id,
        int $amount,
        //        ExpenseType $type,
        ?Account $account,
        DateTime $dueDate,
    ) {
        $this->id = $id;
        $this->amount = $amount;
        $this->dueDate = $dueDate;
        //        $this->type = $type;
        $this->account = $account;
        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(
        ExpenseId $id,
        ExpenseAmount $amount,
        //        ExpenseType $type,
        ?Account $account,
        ExpenseDueDate $dueDate,
    ): self {
        $expense = new self($id->value(), $amount->value(), $account, $dueDate->toDateTime());

        $expense->record(new ExpenseWasEntered(
            $id->value(),
            $amount->value(),
            $account->id(),
            $dueDate->value()

        ));

        return $expense;
    }

    public static function createWithDescription(
        ExpenseId $id,
        ExpenseAmount $amount,
        //        ExpenseType $type,
        ?Account $account,
        ExpenseDueDate $dueDate,
        ExpenseDescription $description,
    ): self {
        $expense = new self($id->value(), $amount->value(), $account, $dueDate->toDateTime());
        $expense->updateDescription($description->value());

        $expense->record(new ExpenseWasEntered(
            $id->value(),
            $amount->value(),
            $account->id(),
            $dueDate->value()

        ));

        return $expense;
    }

    public function compensate(): void
    {
        $event = new ExpenseWasCompensated(
            aggregateId: $this->id,
            amount: - $this->amount,
            accountId: $this->account->id(),
            dueDate: $this->dueDate->format('Y-m-d')
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

    private function applyExpenseWasCompensated(ExpenseWasCompensated $event): void
    {
        $this->amount -= $event->toPrimitives()['amount'];
    }
}
