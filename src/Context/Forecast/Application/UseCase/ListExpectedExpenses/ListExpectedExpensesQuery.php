<?php

declare(strict_types=1);

namespace App\Context\Forecast\Application\UseCase\ListExpectedExpenses;

use App\Shared\Application\Query;

final readonly class ListExpectedExpensesQuery implements Query
{
    public function __construct(
        private ?int $year = null,
        private bool $activeOnly = true,
    ) {}

    public function year(): ?int
    {
        return $this->year;
    }

    public function activeOnly(): bool
    {
        return $this->activeOnly;
    }
}
