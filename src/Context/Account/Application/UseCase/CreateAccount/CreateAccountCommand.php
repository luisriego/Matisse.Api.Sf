<?php

declare(strict_types=1);

namespace App\Context\Account\Application\UseCase\CreateAccount;

use App\Shared\Application\Command;

final readonly class CreateAccountCommand implements Command
{
    public function __construct(
        private string $id,
        private string $name,
        /** Balance in cents (same unit as SetInitialBalance). */
        private int $initialBalanceAmount,
        /** ISO date Y-m-d (posting date of the opening balance). */
        private string $initialBalanceDate,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function initialBalanceAmount(): int
    {
        return $this->initialBalanceAmount;
    }

    public function initialBalanceDate(): string
    {
        return $this->initialBalanceDate;
    }
}
