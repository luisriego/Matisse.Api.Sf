<?php

declare(strict_types=1);

namespace App\Context\Ledger\Application\UseCase\FindLedgerMovements;

use App\Shared\Application\Query;

final readonly class FindLedgerMovementsQuery implements Query
{
    public function __construct(
        private int $year,
        private int $month,
        private ?string $accountId = null,
    ) {}

    public function year(): int
    {
        return $this->year;
    }

    public function month(): int
    {
        return $this->month;
    }

    public function accountId(): ?string
    {
        return $this->accountId;
    }
}
