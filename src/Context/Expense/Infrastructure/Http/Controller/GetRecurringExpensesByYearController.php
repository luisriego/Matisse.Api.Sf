<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\Query\GetRecurringExpensesByYearQuery;
use App\Context\Expense\Application\Query\GetRecurringExpensesByYearQueryHandler;
use App\Context\Expense\Domain\RecurringExpense;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function array_map;
use function sprintf;

class GetRecurringExpensesByYearController
{
    private GetRecurringExpensesByYearQueryHandler $queryHandler;

    public function __construct(GetRecurringExpensesByYearQueryHandler $queryHandler)
    {
        $this->queryHandler = $queryHandler;
    }

    public function __invoke(int $year, Request $request): JsonResponse
    {
        $query = new GetRecurringExpensesByYearQuery($year);
        $recurringExpenses = ($this->queryHandler)($query);

        $responseData = array_map(function (RecurringExpense $expense) {
            return [
                'id' => $expense->id(),
                'description' => $expense->description(),
                'amount' => $expense->amount(),
                'dueDay' => $expense->dueDay(),
                'startDate' => $expense->startDate()->format('Y-m-d'),
                'endDate' => $expense->endDate() ? $expense->endDate()->format('Y-m-d') : null,
                'monthsOfYear' => $expense->monthsOfYear(),
                'isActive' => $expense->isActive(),
                'hasPredefinedAmount' => $expense->hasPredefinedAmount(),
            ];
        }, $recurringExpenses);

        return new JsonResponse([
            'message' => sprintf('Recurring expenses for year %d', $year),
            'year' => $year,
            'expenses' => $responseData,
        ], Response::HTTP_OK);
    }
}
