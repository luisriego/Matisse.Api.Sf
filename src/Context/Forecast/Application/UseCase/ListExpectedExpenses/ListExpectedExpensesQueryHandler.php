<?php

declare(strict_types=1);

namespace App\Context\Forecast\Application\UseCase\ListExpectedExpenses;

use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Forecast\Application\Service\ExpectedExpenseListMapper;
use App\Shared\Application\QueryHandler;

use function array_filter;
use function array_values;
use function mb_strtolower;
use function strcmp;
use function usort;

final readonly class ListExpectedExpensesQueryHandler implements QueryHandler
{
    public function __construct(
        private RecurringExpenseRepository $recurringExpenseRepository,
        private ExpectedExpenseListMapper $expectedExpenseListMapper,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(ListExpectedExpensesQuery $query): array
    {
        $recurringExpenses = $query->year() !== null
            ? $this->recurringExpenseRepository->findByYear($query->year())
            : $this->recurringExpenseRepository->findAll();

        if ($query->activeOnly()) {
            $recurringExpenses = array_values(array_filter(
                $recurringExpenses,
                static fn (RecurringExpense $re) => $re->isActive(),
            ));
        }

        usort(
            $recurringExpenses,
            static fn (RecurringExpense $a, RecurringExpense $b): int => strcmp(
                mb_strtolower((string) ($a->description() ?? $a->type()->name() ?? '')),
                mb_strtolower((string) ($b->description() ?? $b->type()->name() ?? '')),
            ),
        );

        $out = [];

        foreach ($recurringExpenses as $recurring) {
            $out[] = $this->expectedExpenseListMapper->map($recurring);
        }

        return $out;
    }
}
