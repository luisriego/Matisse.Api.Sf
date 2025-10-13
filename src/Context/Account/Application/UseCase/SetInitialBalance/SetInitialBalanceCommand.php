<?php

declare(strict_types=1);

namespace App\Context\Account\Application\UseCase\SetInitialBalance;

use App\Shared\Application\Command;

readonly class SetInitialBalanceCommand implements Command
{
    public function __construct(
        private string $accountId,
        private int $amount,
        private string $date,
    ) {}

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function date(): string
    {
        return $this->date;
    }
}
