<?php

namespace App\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Application\UseCase\FindAccount\FindAccountQuery;
use App\Context\Account\Domain\Exception\AccountNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetAccountController
{
    public function __construct(
        #[Autowire(service: 'query.bus')]
        private MessageBusInterface $queryBus
    ) {}

    #[Route('/{id}', name: 'get_account_by_id', methods: ['GET'])]
    public function __invoke(string $id) : JsonResponse
    {
        try {
            $query = new FindAccountQuery($id);
            $envelope = $this->queryBus->dispatch($query);
            $accountData = $envelope->last(HandledStamp::class)->getResult();

            return new JsonResponse($accountData, Response::HTTP_OK);
        } catch (AccountNotFoundException) {
            return new JsonResponse(['error' => 'Account not found'], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}