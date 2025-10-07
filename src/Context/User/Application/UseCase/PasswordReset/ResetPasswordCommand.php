<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\PasswordReset;

use App\Shared\Application\Command;

final readonly class ResetPasswordCommand implements Command
{
    public function __construct(
        private string $userId,
        private string $token,
        private string $newPassword,
    ) {}

    public function userId(): string
    {
        return $this->userId;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function newPassword(): string
    {
        return $this->newPassword;
    }
}
