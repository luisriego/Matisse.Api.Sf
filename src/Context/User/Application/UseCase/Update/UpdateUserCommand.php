<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\Update;

use App\Shared\Application\Command;

final readonly class UpdateUserCommand implements Command
{
    public function __construct(
        private string $id,
        private string $name,
        private string $lastName,
        private string $gender,
        private string $phoneNumber,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getGender(): string
    {
        return $this->gender;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }
}
