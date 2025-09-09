<?php

declare(strict_types=1);

namespace App\Context\User\Domain;

use App\Context\User\Domain\Event\CreateUserDomainEvent;
use App\Context\User\Domain\ValueObject\Email;
use App\Context\User\Domain\ValueObject\Password;
use App\Context\User\Domain\ValueObject\UserId;
use App\Context\User\Domain\ValueObject\UserName;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\ValueObject\Uuid as CustomUuid; // <--- Añadida esta línea para nuestro Uuid
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid; // Mantener este use si se usa en otro lugar

use function array_unique;
use function sha1;
use function uniqid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'users')]
class User extends AggregateRoot implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const int MIN_AGE = 18;
    public const int NAME_MIN_LENGTH = 2;
    public const int NAME_MAX_LENGTH = 80;
    public const int MIN_PASSWORD_LENGTH = 6;
    public const int MAX_PASSWORD_LENGTH = 55;
    public const int ID_LENGTH = 36;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, options: ['fixed' => true])]
    private string $id;

    #[ORM\Column(type: 'string', length: 80)]
    private ?string $name;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private readonly ?string $email;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    #[ORM\Column(type: 'string', length: 40, nullable: true)]
    private ?string $token;

    #[ORM\Column(type: 'string', length: 255, options: [
        'comment' => 'The hashed password',
    ])]
    private ?string $password;

    #[ORM\Column(type: 'smallint')]
    private int $age;

    #[ORM\Column(type: 'boolean')]
    private ?bool $isActive = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $updatedAt = null;

    private function __construct(
        UserId $id,
        UserName $name,
        Email $email,
        int $age,
    ) {
        $this->id = (string) $id->value();
        $this->name = $name->value();
        $this->email = $email->value();
        $this->token = sha1(uniqid('', true));
        $this->age = $age;
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
        int $age,
    ): self {
        $user = new self($id, $name, $email, $age);
        $user->hashPassword($password->value(), $passwordHasher);

        $user->record(
            new CreateUserDomainEvent(
                $user->id,
                $user->name,
                $user->email,
                $user->getPassword(),
                CustomUuid::random()->value(), // <--- Cambiado a CustomUuid::random()
                (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            ),
        );

        return $user;
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

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): void
    {
        if ($age < self::MIN_AGE) {
            throw InvalidArgumentException::createFromMin(self::MIN_AGE);
        }
        $this->age = $age;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): void
    {
        $this->token = $token;
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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'age' => $this->age,
            'roles' => $this->roles,
            'isActive' => $this->isActive,
            'createdOn' => $this->createdAt,
            'updatedOn' => $this->updatedAt,
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
