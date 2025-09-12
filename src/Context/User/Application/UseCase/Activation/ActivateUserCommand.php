<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\Activation;

use App\Shared\Application\Command;

final readonly class ActivateUserCommand implements Command
{
    public function __construct(
        private string $userId,
        private string $token,
    ) {}

    public function userId(): string
    {
        return $this->userId;
    }

    public function token(): string
    {
        return $this->token;
    }
}
