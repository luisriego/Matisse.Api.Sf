<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\GetPendingMonthlyRecurringExpenses\GetPendingMonthlyRecurringExpensesQuery;
use App\Shared\Domain\Exception\InvalidDataException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function array_map;

final class GetPendingMonthlyRecurringExpensesController extends ApiController
{
    /**
     * @throws Exception
     */
    public function __invoke(int $month, int $year, Request $request): JsonResponse
    {
        $accountId = $request->query->get('accountId');

        $query = new GetPendingMonthlyRecurringExpensesQuery(
            $month,
            $year,
            $accountId ? (string) $accountId : null,
        );

        $recurringExpenses = $this->ask($query);

        $data = array_map(function ($recurringExpense) {
            return [
                'id' => $recurringExpense->id(),
                'accountId' => $recurringExpense->accountId(), // Added accountId
                'amount' => $recurringExpense->amount(),
                'type' => $recurringExpense->type()->id(),
                'dueDay' => $recurringExpense->dueDay(),
                'monthsOfYear' => $recurringExpense->monthsOfYear(),
                'startDate' => $recurringExpense->startDate()->format('Y-m-d'),
                'endDate' => $recurringExpense->endDate()?->format('Y-m-d'),
                'description' => $recurringExpense->description(),
                'notes' => $recurringExpense->notes(),
                'hasPredefinedAmount' => $recurringExpense->hasPredefinedAmount(),
            ];
        }, $recurringExpenses);

        return new JsonResponse($data, Response::HTTP_OK);
    }

    protected function exceptions(): array
    {
        return [
            InvalidDataException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
