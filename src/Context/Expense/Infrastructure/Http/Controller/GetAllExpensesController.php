<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\FindAllExpenses\FindAllExpensesQuery;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final readonly class GetAllExpensesController
{
    public function __construct(
        #[Autowire(service: 'query.bus')]
        private MessageBusInterface $queryBus,
    ) {}

    #[Route('', name: 'get_all_expenses', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        try {
            $query = new FindAllExpensesQuery();
            $envelope = $this->queryBus->dispatch($query);
            $expensesData = $envelope->last(HandledStamp::class)->getResult();

            return new JsonResponse($expensesData, Response::HTTP_OK);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
