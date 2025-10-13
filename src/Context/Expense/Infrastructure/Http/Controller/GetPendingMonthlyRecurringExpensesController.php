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
                'id' => $recurringExpense->id(), // Corrected: id() returns string
                'amount' => $recurringExpense->amount(), // Corrected: amount() returns int
                'type' => $recurringExpense->type()->id(), // Corrected: type()->id() returns string
                'dueDay' => $recurringExpense->dueDay(), // Corrected: dueDay() returns int
                'monthsOfYear' => $recurringExpense->monthsOfYear(), // Correct: monthsOfYear() returns array
                'startDate' => $recurringExpense->startDate()->format('Y-m-d'), // Corrected: startDate() returns DateTimeInterface
                'endDate' => $recurringExpense->endDate()?->format('Y-m-d'), // Corrected: endDate() returns ?DateTimeInterface, handle null
                'description' => $recurringExpense->description(), // Corrected: description() returns ?string
                'notes' => $recurringExpense->notes(), // Corrected: notes() returns ?string
                'hasPredefinedAmount' => $recurringExpense->hasPredefinedAmount(), // Corrected: hasPredefinedAmount() returns bool
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
