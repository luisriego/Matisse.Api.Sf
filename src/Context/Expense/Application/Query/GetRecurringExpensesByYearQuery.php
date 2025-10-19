<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\Query;

final class GetRecurringExpensesByYearQuery
{
    private int $year;

    public function __construct(int $year)
    {
        $this->year = $year;
    }

    public function year(): int
    {
        return $this->year;
    }
}
