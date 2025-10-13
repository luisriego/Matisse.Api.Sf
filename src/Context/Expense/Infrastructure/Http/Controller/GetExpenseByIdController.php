<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Account\Domain\Exception\ExpenseNotFoundException;
use App\Context\Expense\Application\UseCase\FindExpense\FindExpenseQuery;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final readonly class GetExpenseByIdController
{
    public function __construct(
        #[Autowire(service: 'query.bus')]
        private MessageBusInterface $queryBus,
    ) {}

    #[Route('/{id}', name: 'get_expense_by_id', methods: ['GET'])]
    public function __invoke(string $id): JsonResponse
    {
        try {
            $query = new FindExpenseQuery($id);
            $envelope = $this->queryBus->dispatch($query);
            $expenseData = $envelope->last(HandledStamp::class)->getResult();

            return new JsonResponse($expenseData, Response::HTTP_OK);
        } catch (ExpenseNotFoundException) {
            return new JsonResponse(['error' => 'Expense not found'], Response::HTTP_NOT_FOUND);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
