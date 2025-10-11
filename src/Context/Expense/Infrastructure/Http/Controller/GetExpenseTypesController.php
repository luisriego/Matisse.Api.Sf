<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\FindTypes\FindTypesQuery;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final readonly class GetExpenseTypesController
{
    public function __construct(
        #[Autowire(service: 'query.bus')]
        private MessageBusInterface $queryBus,
    ) {}

    public function __invoke(): JsonResponse
    {
        $query = new FindTypesQuery();
        $envelope = $this->queryBus->dispatch($query);
        $types = $envelope->last(HandledStamp::class)->getResult();

        return new JsonResponse($types, Response::HTTP_OK);
    }
}
