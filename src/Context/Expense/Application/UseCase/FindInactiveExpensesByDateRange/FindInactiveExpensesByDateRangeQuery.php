<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\FindInactiveExpensesByDateRange;

use App\Shared\Application\Query;
use App\Shared\Domain\ValueObject\DateRange;

final readonly class FindInactiveExpensesByDateRangeQuery implements Query
{
    public function __construct(private DateRange $dateRange) {}

    public function dateRange(): DateRange
    {
        return $this->dateRange;
    }
}
