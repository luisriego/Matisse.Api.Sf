<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\FindActiveExpensesByDateRange;

use App\Shared\Application\Query;
use App\Shared\Domain\ValueObject\DateRange;

final readonly class FindActiveExpensesByDateRangeQuery implements Query
{
    public function __construct(private DateRange $dateRange) {}

    public function dateRange(): DateRange
    {
        return $this->dateRange;
    }
}
