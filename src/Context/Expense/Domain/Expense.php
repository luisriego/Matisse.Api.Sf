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
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'expenses')]
class Expense extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'integer')]
    private int $amount;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = '';

    #[ORM\Column(type: 'datetime')]
    private DateTime $dueDate;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $paidAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\ManyToOne(targetEntity: Account::class, fetch: 'LAZY')]
    #[ORM\JoinColumn(name: 'account_id', referencedColumnName: 'id', nullable: true)]
    private ?Account $account;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $residentUnitId = null; // Added property

    #[ORM\ManyToOne(targetEntity: ExpenseType::class, fetch: 'LAZY')]
    #[ORM\JoinColumn(name: 'expense_type_id', referencedColumnName: 'id', nullable: true)]
    private ?ExpenseType $type = null;

    #[ORM\ManyToOne(targetEntity: RecurringExpense::class, fetch: 'LAZY')]
    #[ORM\JoinColumn(name: 'recurring_expense_id', referencedColumnName: 'id', nullable: true)]
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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'description' => $this->description,
            'dueDate' => $this->dueDate->format('Y-m-d H:i:s'),
            'paidAt' => $this->paidAt?->format('Y-m-d H:i:s'),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'residentUnitId' => $this->residentUnitId,
            'type' => $this->type?->toArray(),
            'account' => $this->account?->toArray(),
            'recurringExpense' => $this->recurringExpense?->id(),
        ];
    }

    private function applyExpenseWasCompensated(ExpenseWasCompensated $event): void
    {
        $this->amount += $event->toPrimitives()['amount'];
    }
}
