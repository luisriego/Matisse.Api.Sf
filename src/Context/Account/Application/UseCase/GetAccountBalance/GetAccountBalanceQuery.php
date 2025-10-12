<?php

declare(strict_types=1);

namespace App\Context\Account\Application\UseCase\GetAccountBalance;

use App\Shared\Application\Query;
use DateTimeImmutable;

readonly class GetAccountBalanceQuery implements Query
{
    public function __construct(
        private string $accountId,
        private ?DateTimeImmutable $upToDate = null,
    ) {
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function upToDate(): DateTimeImmutable
    {
        return $this->upToDate ?? new DateTimeImmutable();
    }
}
