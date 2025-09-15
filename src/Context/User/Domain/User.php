<?php

declare(strict_types=1);

namespace App\Context\User\Domain;

use App\Context\User\Domain\Event\CreateUserDomainEvent;
use App\Context\User\Domain\ValueObject\Email;
use App\Context\User\Domain\ValueObject\Password;
use App\Context\User\Domain\ValueObject\UserId;
use App\Context\User\Domain\ValueObject\UserName;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\Uuid as CustomUuid;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Context\ResidentUnit\Domain\ResidentUnit; // Importar ResidentUnit

use function array_unique;
use function sha1;
use function uniqid;

class User extends AggregateRoot implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const int NAME_MIN_LENGTH = 2;
    public const int NAME_MAX_LENGTH = 80;
    public const int MIN_PASSWORD_LENGTH = 6;
    public const int MAX_PASSWORD_LENGTH = 55;
    public const int ID_LENGTH = 36;

    private string $id;
    private ?string $name;
    private readonly ?string $email;
    private $roles = [];
    private ?string $confirmationToken;
    private ?string $password;
    private ?bool $isActive = false;
    private ?DateTimeImmutable $createdAt = null;
    private ?DateTime $updatedAt = null;
    private ?ResidentUnit $residentUnit = null; // Propiedad para la relación

    private function __construct(
        UserId $id,
        UserName $name,
        Email $email,
    ) {
        $this->id = (string) $id->value();
        $this->name = $name->value();
        $this->email = $email->value();
        $this->confirmationToken = sha1(uniqid('', true));
        $this->isActive = false;
        $this->createdAt = new DateTimeImmutable();
        $this->markAsUpdated();
    }

    public static function create(
        UserId $id,
        UserName $name,
        Email $email,
        Password $password,
        UserPasswordHasherInterface $passwordHasher,
        ?ResidentUnit $residentUnit = null // Añadir ResidentUnit como parámetro opcional
    ): self {
        $user = new self($id, $name, $email);
        $user->hashPassword($password->value(), $passwordHasher);
        $user->setResidentUnit($residentUnit); // Asignar la unidad residencial

        $user->record(
            new CreateUserDomainEvent(
                $user->id,
                $user->name,
                $user->email,
                $user->getPassword(),
                CustomUuid::random()->value(),
                (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            ),
        );

        return $user;
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->confirmationToken = null;
        $this->markAsUpdated();
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $userName = UserName::fromString($name);
        $this->name = $userName->value();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getUsername(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function hashPassword(string $plainPassword, UserPasswordHasherInterface $hasher): self
    {
        $this->password = $hasher->hashPassword($this, $plainPassword);

        return $this;
    }

    public function hashedPassword(string $hashedPassword): self
    {
        $this->password = $hashedPassword;

        return $this;
    }

    public function changePassword(string $newPassword, UserPasswordHasherInterface $hasher): void
    {
        $this->hashPassword($newPassword, $hasher);

        $this->markAsUpdated();
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void {}

    public function getResidentUnit(): ?ResidentUnit
    {
        return $this->residentUnit;
    }

    public function setResidentUnit(?ResidentUnit $residentUnit): self
    {
        $this->residentUnit = $residentUnit;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'roles' => $this->roles,
            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'residentUnitId' => $this->residentUnit?->id(), // Incluir el ID de la unidad residencial
        ];
    }

    public function equals(User $user): bool
    {
        return $this->getId() === $user->getId();
    }

    private function markAsUpdated(): void
    {
        $this->updatedAt = new DateTime();
    }
}
