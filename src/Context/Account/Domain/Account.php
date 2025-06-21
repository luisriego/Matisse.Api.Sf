<?php

declare(strict_types=1);

namespace App\Context\Account\Domain;

use App\Context\Account\Domain\Bus\AccountWasDisabled;
use App\Context\Account\Domain\Bus\AccountWasEnabled;
use App\Shared\Domain\AggregateRoot;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Account extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, options: ['fixed' => true])]
    private string $id;

    #[ORM\Column(length: 16, unique: true)]
    private string $code;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $isActive = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $updatedAt = null;

    //    #[ORM\OneToMany(targetEntity: Expense::class, mappedBy: 'account')]
    //    private Collection $expenses;

    public function __construct(string $id, string $code, string $name)
    {
        $this->id = $id;
        $this->code = $code;
        $this->name = $name;
        $this->isActive = false;
        $this->createdAt = new DateTimeImmutable();

        //        $this->expenses = new ArrayCollection();
    }

    public static function create(AccountId $id, AccountCode $code, AccountName $name): self
    {
        return new self($id->value(), $code->value(), $name->value());
    }

    public static function createWithDescription(
        AccountId $id,
        AccountCode $code,
        AccountName $name,
        AccountDescription $description
    ): self
    {
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
            'isActive' => $this->isActive,
        ];
    }
}
