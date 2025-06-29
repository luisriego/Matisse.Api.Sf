<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\FindExpense;

use App\Shared\Application\Query;

final readonly class FindExpenseQuery implements Query
{
    public function __construct(private string $id) {}

    public function id(): string
    {
        return $this->id;
    }
}