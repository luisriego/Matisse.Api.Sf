<?php

declare(strict_types=1);

namespace App\Context\User\Domain;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\User\Domain\Event\UserWasRegistered;
use App\Context\User\Domain\ValueObject\Email;
use App\Context\User\Domain\ValueObject\Password;
use App\Context\User\Domain\ValueObject\UserId;
use App\Context\User\Domain\ValueObject\UserName;
use App\Shared\Domain\AggregateRoot;
use DateTime;
use DateTimeImmutable;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface; // Importar ResidentUnit

use function array_filter;
use function array_unique;
use function array_values;
use function bin2hex;
use function in_array;
use function random_bytes;

class User extends AggregateRoot implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const int NAME_MIN_LENGTH = 2;
    public const int NAME_MAX_LENGTH = 80;
    public const int MIN_PASSWORD_LENGTH = 6;
    public const int MAX_PASSWORD_LENGTH = 55;
    public const int ID_LENGTH = 36;
    public const string ROLE_SYNDIC = 'ROLE_SYNDIC';

    private string $id;
    private ?string $name;
    private ?string $lastName = null;
    private ?string $gender = null;
    private ?string $phoneNumber = null;
    private readonly ?string $email;
    private $roles = [];
    private ?string $confirmationToken;
    private ?string $passwordResetToken = null;
    private ?DateTime $passwordResetRequestedAt = null;
    private ?string $password;
    private ?bool $isActive = false;
    private ?DateTimeImmutable $createdAt = null;
    private ?DateTime $updatedAt = null;
    private ?ResidentUnit $residentUnit = null; // Propiedad para la relación
    private ?string $avatar = null; // <-- Añadido de nuevo

    private function __construct(
        UserId $id,
        UserName $name,
        Email $email,
    ) {
        $this->id = (string) $id->value();
        $this->name = $name->value();
        $this->email = $email->value();
        $this->confirmationToken = bin2hex(random_bytes(32));
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
        ?ResidentUnit $residentUnit = null,
    ): self {
        $user = new self($id, $name, $email);
        $user->hashPassword($password->value(), $passwordHasher);
        $user->setResidentUnit($residentUnit);

        $user->record(
            new UserWasRegistered(
                $user->id,
                $user->name,
                $user->email,
                $user->confirmationToken,
            ),
        );

        return $user;
    }

    /**
     * Invited resident: inactive, no password until they confirm email and set one.
     */
    public static function invite(
        UserId $id,
        UserName $name,
        Email $email,
        ResidentUnit $residentUnit,
    ): self {
        $user = new self($id, $name, $email);
        $user->password = null;
        $user->setResidentUnit($residentUnit);

        $user->record(
            new UserWasRegistered(
                $user->id,
                $user->name,
                $user->email,
                $user->confirmationToken,
            ),
        );

        return $user;
    }

    public function needsPasswordSetup(): bool
    {
        return null === $this->password;
    }

    public function update(
        string $name,
        string $lastName,
        string $gender,
        string $phoneNumber,
    ): void {
        $this->updateName($name);
        $this->lastName = $lastName;
        $this->gender = $gender;
        $this->phoneNumber = $phoneNumber;
        $this->markAsUpdated();
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

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function getPasswordResetRequestedAt(): ?DateTime
    {
        return $this->passwordResetRequestedAt;
    }

    public function clearPasswordResetToken(): void
    {
        $this->passwordResetToken = null;
        $this->passwordResetRequestedAt = null;
        $this->markAsUpdated();
    }

    public function requestPasswordReset(): void
    {
        $this->passwordResetToken = bin2hex(random_bytes(32));
        $this->passwordResetRequestedAt = new DateTime();
        $this->markAsUpdated();
    }

    public function resetPassword(string $newPassword, UserPasswordHasherInterface $hasher): void
    {
        $this->hashPassword($newPassword, $hasher);
        $this->passwordResetToken = null;
        $this->passwordResetRequestedAt = null;
        $this->markAsUpdated();
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function updateName(?string $name): void
    {
        $userName = UserName::fromString($name);
        $this->name = $userName->value();
    }

    public function lastName(): ?string
    {
        return $this->lastName;
    }

    public function gender(): ?string
    {
        return $this->gender;
    }

    public function phoneNumber(): ?string
    {
        return $this->phoneNumber;
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

    public function isSyndic(): bool
    {
        return in_array(self::ROLE_SYNDIC, $this->roles, true);
    }

    public function promoteToSyndic(): void
    {
        if ($this->isSyndic()) {
            return;
        }

        $this->roles[] = self::ROLE_SYNDIC;
        $this->roles = array_values(array_unique($this->roles));
        $this->markAsUpdated();
    }

    public function demoteToResident(): void
    {
        if (!$this->isSyndic()) {
            return;
        }

        $this->roles = array_values(
            array_filter($this->roles, static fn (string $role): bool => self::ROLE_SYNDIC !== $role),
        );
        $this->markAsUpdated();
    }

    public function getPassword(): string
    {
        return $this->password ?? '';
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

    public function equals(User $user): bool
    {
        return $this->getId() === $user->getId();
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function avatar(): ?string
    {
        return $this->avatar;
    }

    public function updateAvatar(?string $avatar): void
    {
        $this->avatar = $avatar;
        $this->markAsUpdated();
    }

    private function markAsUpdated(): void
    {
        $this->updatedAt = new DateTime();
    }
}
