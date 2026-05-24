<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Application\UseCase\FindAccount\FindAccountQuery;
use App\Context\Account\Domain\Exception\AccountNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[OA\Get(
    path: '/api/v1/accounts/{id}',
    summary: 'Get account by ID',
    tags: ['Accounts'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Account details.'),
        new OA\Response(response: 404, description: 'Account not found.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class GetAccountController extends ApiController
{
    /**
     * @throws Throwable
     */
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
