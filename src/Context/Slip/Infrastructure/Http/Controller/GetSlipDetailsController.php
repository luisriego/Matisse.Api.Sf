<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Application\UseCase\GetSlipDetails\GetSlipDetailsQuery;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[OA\Get(
    path: '/api/v1/slips/{id}',
    summary: 'Get slip details by ID',
    tags: ['Slips'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Slip details.'),
        new OA\Response(response: 404, description: 'Slip not found.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class GetSlipDetailsController extends ApiController
{
    /**
     * @throws Throwable
     */
    public function __invoke(string $id): JsonResponse
    {
        $query = new GetSlipDetailsQuery($id);

        $slipDetails = $this->ask($query);

        return new JsonResponse(
            $slipDetails,
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
