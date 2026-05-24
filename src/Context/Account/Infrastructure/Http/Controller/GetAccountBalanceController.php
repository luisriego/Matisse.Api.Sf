<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Application\UseCase\GetAccountBalance\GetAccountBalanceQuery;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[OA\Get(
    path: '/api/v1/accounts/{id}/balance',
    summary: 'Get account balance',
    tags: ['Accounts'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'upToDate', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'),
            description: 'Balance as of this date (Y-m-d). Defaults to today.'),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Account balance.'),
        new OA\Response(response: 404, description: 'Account not found.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class GetAccountBalanceController extends ApiController
{
    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $upToDate = $request->query->get('upToDate');
        $upToDate = $upToDate ? new DateTimeImmutable($upToDate) : null;

        $query = new GetAccountBalanceQuery($id, $upToDate);

        $balance = $this->ask($query);

        return new JsonResponse(
            [
                'account_id' => $id,
                'balance' => $balance,
                'up_to_date' => $upToDate ? $upToDate->format('Y-m-d') : (new DateTimeImmutable())->format('Y-m-d'),
            ],
            Response::HTTP_OK,
        );
    }

    public function exceptions(): array
    {
        return [
            ResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
