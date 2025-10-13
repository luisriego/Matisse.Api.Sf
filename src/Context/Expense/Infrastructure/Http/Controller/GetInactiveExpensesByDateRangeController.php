<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\FindInactiveExpensesByDateRange\FindInactiveExpensesByDateRangeQuery;
use App\Shared\Domain\ValueObject\DateRange;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Throwable;

final readonly class GetInactiveExpensesByDateRangeController
{
    public function __construct(
        #[Autowire(service: 'query.bus')]
        private MessageBusInterface $queryBus,
    ) {}

    public function __invoke(int $year, int $month): JsonResponse
    {
        try {
            $dateRange = DateRange::fromMonth($year, $month);
            $query = new FindInactiveExpensesByDateRangeQuery($dateRange);

            $envelope = $this->queryBus->dispatch($query);
            $handledStamp = $envelope->last(HandledStamp::class);

            if (!$handledStamp instanceof HandledStamp) {
                return new JsonResponse(['error' => 'Query not handled'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $expensesData = $handledStamp->getResult();

            return new JsonResponse($expensesData, Response::HTTP_OK);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
