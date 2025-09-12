<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\ChangePassword;

use App\Shared\Application\Command;

final readonly class ChangePasswordCommand implements Command
{
    public function __construct(
        private string $userId, // This will be the user's email
        private string $oldPassword,
        private string $newPassword,
    ) {}

    public function userId(): string
    {
        return $this->userId;
    }

    public function oldPassword(): string
    {
        return $this->oldPassword;
    }

    public function newPassword(): string
    {
        return $this->newPassword;
    }
}
