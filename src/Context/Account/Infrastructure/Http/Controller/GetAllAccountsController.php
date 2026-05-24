<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Application\UseCase\FindAllAccounts\FindAllAccountsQuery;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Throwable;

#[OA\Get(
    path: '/api/v1/accounts',
    summary: 'List all accounts',
    tags: ['Accounts'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'List of accounts.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final readonly class GetAllAccountsController
{
    public function __construct(
        #[Autowire(service: 'query.bus')]
        private MessageBusInterface $queryBus,
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $query = new FindAllAccountsQuery();
            $envelope = $this->queryBus->dispatch($query);
            $accountsData = $envelope->last(HandledStamp::class)->getResult();

            return new JsonResponse($accountsData, Response::HTTP_OK);
        } catch (Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
