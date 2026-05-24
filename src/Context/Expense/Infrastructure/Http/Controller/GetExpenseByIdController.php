<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Account\Domain\Exception\ExpenseNotFoundException;
use App\Context\Expense\Application\UseCase\FindExpense\FindExpenseQuery;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class GetExpenseByIdController extends ApiController
{
    public function __invoke(string $id): JsonResponse
    {
        $expenseData = $this->ask(new FindExpenseQuery($id));

        return new JsonResponse($expenseData, Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [
            ExpenseNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
