<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\ConfirmationResend;

use App\Shared\Application\Command;

final readonly class ResendConfirmationEmailCommand implements Command
{
    public function __construct(private string $email) {}

    public function email(): string
    {
        return $this->email;
    }
}
