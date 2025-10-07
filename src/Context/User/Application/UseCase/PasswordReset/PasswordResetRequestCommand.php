<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\PasswordReset;

use App\Shared\Application\Command;

final readonly class PasswordResetRequestCommand implements Command
{
    public function __construct(
        private string $email,
    ) {}

    public function email(): string
    {
        return $this->email;
    }
}
