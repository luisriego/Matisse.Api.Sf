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

class ExpenseType
{
    public const string EQUAL = 'EQUAL';
    public const string FRACTION = 'FRACTION';
    public const string INDIVIDUAL = 'INDIVIDUAL';

    private string $id;

    private ?string $code = null;

    private ?string $name = null;

    private ?string $distributionMethod = 'EQUAL';

    private ?string $description = null;

    private Collection $expenses;

    public function __construct(
        string $id,
        string $code,
        string $name,
        string $distributionMethod = self::EQUAL,
        ?string $description = null
    )
    {
        $this->id                 = $id;
        $this->code               = $code;
        $this->name               = $name;
        $this->distributionMethod = $distributionMethod;
        $this->description        = $description;
        $this->expenses           = new ArrayCollection();
    }

    public static function create(
        ExpenseTypeId $id,
        ExpenseTypeCode $code,
        ExpenseTypeName $name,
        ExpenseTypeDistributionMethod $distributionMethod,
        ExpenseTypeDescription $description,
        ?ExpenseStartDate $startDate = null,
        ?ExpenseEndDate $endDate = null
    ): self {
        return new self(
            $id->value(),
            $code->value(),
            $name->value(),
            $distributionMethod->value(),
            $description->value());
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