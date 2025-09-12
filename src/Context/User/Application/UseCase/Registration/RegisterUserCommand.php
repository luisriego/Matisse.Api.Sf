<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\Registration;

use App\Shared\Application\Command;

final readonly class RegisterUserCommand implements Command
{
    public function __construct(
        private string $id,
        private string $name,
        private string $email,
        private string $password,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function password(): string
    {
        return $this->password;
    }
}
