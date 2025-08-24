<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain;

use App\Context\Expense\Domain\ValueObject\ExpenseEndDate;
use App\Context\Expense\Domain\ValueObject\ExpenseStartDate;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeCode;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeDescription;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeDistributionMethod;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeId;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeName;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity] // <-- AÑADIDO
#[ORM\Table(name: 'expense_types')] // <-- AÑADIDO
class ExpenseType
{
    public const string EQUAL = 'EQUAL';
    public const string FRACTION = 'FRACTION';
    public const string INDIVIDUAL = 'INDIVIDUAL';

    #[ORM\Id] // <-- AÑADIDO
    #[ORM\Column(type: 'string', length: 36)] // <-- AÑADIDO
    private string $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)] // <-- AÑADIDO
    private ?string $code = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)] // <-- AÑADIDO
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)] // <-- AÑADIDO
    private ?string $distributionMethod = 'EQUAL';

    #[ORM\Column(type: 'text', nullable: true)] // <-- AÑADIDO
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'expenseType', targetEntity: Expense::class)] // <-- AÑADIDO
    private Collection $expenses;

    // --- RELACIÓN CORREGIDA ---
    #[ORM\OneToMany(mappedBy: 'expenseType', targetEntity: RecurringExpense::class)] // <-- AÑADIDO
    private Collection $recurringExpenses;

    public function __construct(
        string $id,
        string $code,
        string $name,
        string $distributionMethod = self::EQUAL,
        ?string $description = null,
    ) {
        $this->id                 = $id;
        $this->code               = $code;
        $this->name               = $name;
        $this->distributionMethod = $distributionMethod;
        $this->description        = $description;
        $this->expenses           = new ArrayCollection();
        $this->recurringExpenses  = new ArrayCollection(); // <-- AÑADIDO
    }

    public static function create(
        ExpenseTypeId $id,
        ExpenseTypeCode $code,
        ExpenseTypeName $name,
        ExpenseTypeDistributionMethod $distributionMethod,
        ExpenseTypeDescription $description,
        ?ExpenseStartDate $startDate = null,
        ?ExpenseEndDate $endDate = null,
    ): self {
        return new self(
            $id->value(),
            $code->value(),
            $name->value(),
            $distributionMethod->value(),
            $description->value(),
        );
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function distributionMethod(): string
    {
        return $this->distributionMethod;
    }

    public function expenses(): Collection
    {
        return $this->expenses;
    }

    public function addExpense(Expense $expense): static
    {
        if (!$this->expenses->contains($expense)) {
            $this->expenses->add($expense);
        }

        return $this;
    }
}
