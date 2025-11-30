<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\FindAllExpenses\FindAllExpensesQuery;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GetAllExpensesController extends ApiController
{
    #[Route('', name: 'get_all_expenses', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $expensesData = $this->ask(new FindAllExpensesQuery());

        return new JsonResponse($expensesData, Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [];
    }
}
