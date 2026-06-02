<?php

declare(strict_types=1);

namespace App\Context\Ledger\Infrastructure\Http\Controller;

use App\Context\Ledger\Application\UseCase\FindLedgerMovements\FindLedgerMovementsQuery;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function is_string;

#[OA\Get(
    path: '/api/v1/ledger/movements/{year}/{month}',
    summary: 'Get ledger movements for a month',
    tags: ['Ledger'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'year', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'month', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 12)),
        new OA\Parameter(
            name: 'accountId',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'string', format: 'uuid'),
            description: 'Filter by ledger account ID',
        ),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Ledger movements.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class LedgerMovementsGetController extends ApiController
{
    public function __invoke(int $year, int $month, Request $request): JsonResponse
    {
        $accountId = $request->query->get('accountId');
        $query     = new FindLedgerMovementsQuery(
            $year,
            $month,
            is_string($accountId) && $accountId !== '' ? $accountId : null,
        );

        return new JsonResponse($this->ask($query), Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [];
    }
}
