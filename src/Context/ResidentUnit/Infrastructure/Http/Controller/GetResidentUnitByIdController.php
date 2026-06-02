<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Application\UseCase\FindResidentUnitById\FindResidentUnitByIdQuery;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[OA\Get(
    path: '/api/v1/resident-unit/{id}',
    summary: 'Get resident unit by ID',
    tags: ['Resident Units'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Resident unit details.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResidentUnit'),
        ),
        new OA\Response(response: 404, description: 'Resident unit not found.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class GetResidentUnitByIdController extends ApiController
{
    /**
     * @throws Throwable
     */
    public function __invoke(string $id): JsonResponse
    {
        $residentUnitData = $this->ask(new FindResidentUnitByIdQuery($id));

        return new JsonResponse($residentUnitData, Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [
            ResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
