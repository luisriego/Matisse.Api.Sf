<?php

declare(strict_types=1);

namespace App\Context\Account\Domain;

use App\Context\Account\Domain\Bus\AccountWasDisabled;
use App\Context\Account\Domain\Bus\AccountWasEnabled;
use App\Context\Expense\Domain\Expense;
use App\Shared\Domain\AggregateRoot;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Account extends AggregateRoot
{
    private string $id;
    private string $code;
    private ?string $name = null;
    private ?string $description = null;
    private ?bool $isActive = false;
    private ?DateTimeImmutable $createdAt = null;
    private ?DateTime $updatedAt = null;
    private Collection $expenses;

    public function __construct(string $id, string $code, string $name)
    {
        $this->id = $id;
        $this->code = $code;
        $this->name = $name;
        $this->isActive = false;
        $this->createdAt = new DateTimeImmutable();
        // Doctrine will manage this collection
    }

    public static function create(AccountId $id, AccountCode $code, AccountName $name): self
    {
        return new self($id->value(), $code->value(), $name->value());
    }

    public static function createWithDescription(
        AccountId $id,
        AccountCode $code,
        AccountName $name,
        AccountDescription $description,
    ): self {
        $account =  new self($id->value(), $code->value(), $name->value());
        $account->updateDescription($description);

        return $account;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function expenses(): Collection
    {
        return $this->expenses;
    }

    public function updateCode(AccountCode $code): void
    {
        $this->code = $code->value();
        $this->markAsUpdated();
    }

    public function updateName(AccountName $name): void
    {
        $this->name = $name->value();
        $this->markAsUpdated();
    }

    public function updateDescription(AccountDescription $description): void
    {
        $this->description = $description->value();
        $this->markAsUpdated();
    }

    public function enable(): void
    {
        $this->isActive = true;
        $this->markAsUpdated();
        $this->record(new AccountWasEnabled($this->id()));
    }

    public function disable(): void
    {
        $this->isActive = false;
        $this->markAsUpdated();
        $this->record(new AccountWasDisabled($this->id()));
    }

    public function addExpense(Expense $expense): void
    {
        if (!$this->expenses->contains($expense)) {
            $this->expenses[] = $expense;
            $expense->setAccount($this);
        }
    }

    public function removeExpense(Expense $expense): void
    {
        $this->expenses->removeElement($expense);
    }

    public function markAsUpdated(): void
    {
        $this->updatedAt = new DateTime();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'isActive' => $this->isActive,
        ];
    }
}
