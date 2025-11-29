<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Application\UseCase\FindAccount\FindAccountQuery;
use App\Context\Account\Domain\Exception\AccountNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class GetAccountController extends ApiController
{
    /**
     * @throws Throwable
     */
    #[Route('/{id}', name: 'get_account_by_id', methods: ['GET'])]
    public function __invoke(string $id): JsonResponse
    {
        $accountData = $this->ask(new FindAccountQuery($id));

        return new JsonResponse($accountData, Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [
            AccountNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
