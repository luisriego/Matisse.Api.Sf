<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Domain;

use App\Context\ResidentUnit\Domain\Event\ResidentUnitIdealFractionWasChanged;
use App\Context\ResidentUnit\Domain\Event\ResidentUnitRecipientsWereReplaced;
use App\Context\ResidentUnit\Domain\Event\ResidentUnitRecipientWasAppended;
use App\Context\User\Domain\User;
use App\Shared\Domain\AggregateRoot;
use DateMalformedStringException;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

// Importar User

// Eliminadas todas las anotaciones #[ORM\...] (se asume que el mapeo se hace por XML)
class ResidentUnit extends AggregateRoot
{
    private ?string $id = null; // Inicializado en la declaración
    private string $unit = ''; // Inicializado en la declaración
    private bool $isActive = false; // Inicializado en la declaración
    private DateTimeImmutable $createdAt;
    private ?DateTime $updatedAt = null; // Inicializado en la declaración
    private array $notificationRecipients;
    private float $idealFraction = 0.0; // Inicializado en la declaración
    private Collection $incomes;
    private Collection $slips;
    private Collection $users;

    private function __construct(string $id, string $unit, float $idealFraction)
    {
        $this->id = $id;
        $this->unit = $unit;
        $this->idealFraction = $idealFraction;
        $this->createdAt = new DateTimeImmutable();
        $this->incomes = new ArrayCollection();
        $this->slips = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->notificationRecipients = []; // Explicitly initialize here
    }

    public static function create(
        ResidentUnitId $id,
        ResidentUnitVO $unit,
        ResidentUnitIdealFraction $idealFraction,
    ): self {
        $residentUnit = new self($id->value(), $unit->value(), $idealFraction->value());
        $residentUnit->isActive = true;
        $residentUnit->markAsUpdated();

        return $residentUnit;
    }

    public static function createWithRecipients(
        ResidentUnitId $id,
        ResidentUnitVO $unit,
        ResidentUnitIdealFraction $idealFraction,
        array $recipients,
    ): self {
        $residentUnit = new self($id->value(), $unit->value(), $idealFraction->value());
        $residentUnit->isActive = true;
        $residentUnit->markAsUpdated();

        $residentUnit->notificationRecipients = $recipients;

        return $residentUnit;
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function unit(): string
    {
        return $this->unit;
    }

    public function idealFraction(): float
    {
        return $this->idealFraction;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function markAsUpdated(): void
    {
        $this->updatedAt = new DateTime();
    }

    public function idealFractionMustNotBeMoreThan1(float $accumulatedIF, float $presentValue): bool
    {
        return IdealFractionSumPolicy::isWithinMaximum($accumulatedIF, $presentValue);
    }

    public function notificationRecipients(): array
    {
        return $this->notificationRecipients;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function replaceRecipients(array $recipients): void
    {
        $this->notificationRecipients = $recipients;
        $this->markAsUpdated();
        $this->record(new ResidentUnitRecipientsWereReplaced($this->id, $this->notificationRecipients));
    }

    /**
     * @throws DateMalformedStringException
     */
    public function appendRecipient(string $name, string $email): void
    {
        $this->notificationRecipients[] = ['name' => $name, 'email' => $email];
        $this->markAsUpdated();
        $this->record(new ResidentUnitRecipientWasAppended($this->id, $name, $email));
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;

            // Asegurarse de que el lado propietario de la relación también se actualice
            if ($user->getResidentUnit() !== $this) {
                $user->setResidentUnit($this);
            }
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getResidentUnit() === $this) {
                $user->setResidentUnit(null);
            }
        }

        return $this;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function changeIdealFraction(ResidentUnitIdealFraction $idealFraction): void
    {
        $this->idealFraction = $idealFraction->value();
        $this->markAsUpdated();
        $this->record(new ResidentUnitIdealFractionWasChanged($this->id, $this->idealFraction));
    }
}
