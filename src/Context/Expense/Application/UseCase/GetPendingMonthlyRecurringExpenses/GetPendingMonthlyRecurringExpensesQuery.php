<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\GetPendingMonthlyRecurringExpenses;

use App\Shared\Application\Query;

final readonly class GetPendingMonthlyRecurringExpensesQuery implements Query
{
    public function __construct(
        private int $month,
        private int $year,
        private ?string $accountId = null,
    ) {}

    public function month(): int
    {
        return $this->month;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function accountId(): ?string
    {
        return $this->accountId;
    }
}
