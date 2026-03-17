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
    private string $id;
    private ?string $name;
    private ?string $code;
    private ?string $description;

    public function __construct(
        string $id,
        ?string $name,
        ?string $code = null,
        ?string $description = null,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->description = $description;
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
            $income->categorizeAs(null);
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
        ];
    }
}
