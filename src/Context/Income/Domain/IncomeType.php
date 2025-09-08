<?php

declare(strict_types=1);

namespace App\Context\Income\Domain;

use App\Context\Income\Domain\ValueObject\IncomeId;
use App\Context\Income\Domain\ValueObject\IncomeTypeCode;
use App\Context\Income\Domain\ValueObject\IncomeTypeName;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class IncomeType
{
    private Collection $incomes;

    public function __construct(
        private readonly string $id,
        private ?string $name,
        private ?string $code = null,
        private ?string $description = null,
    ) {
        $this->incomes = new ArrayCollection();
    }

    public static function create(
        IncomeId $id,
        IncomeTypeName $name,
        IncomeTypeCode $code,
        string $description,
        ?Income $incomes = null,
    ): self {
        return new self(
            $id->value(),
            $name->value(),
            $code->value(),
            $description,
            $incomes,
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function code(): ?string
    {
        return $this->code;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function incomes(): Collection
    {
        return $this->incomes;
    }

    public function addIncome(Income $income): void
    {
        if (!$this->incomes->contains($income)) {
            $this->incomes->add($income);
        }
    }

    public function removeIncome(Income $income): void
    {
        if ($this->incomes->removeElement($income)) {
            $income->setIncomeType(null);
        }
    }
}
