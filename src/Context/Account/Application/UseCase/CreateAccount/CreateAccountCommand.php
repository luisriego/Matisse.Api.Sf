<?php

declare(strict_types=1);

namespace App\Context\Account\Application\UseCase\CreateAccount;

use App\Shared\Application\Command;

final readonly class CreateAccountCommand implements Command
{
    public function __construct(
        private string $id,
        private string $code,
        private string $name,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }
}
