<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Domain\Exception\IdealFractionSumExceedsLimitException;
use App\Context\ResidentUnit\Infrastructure\Http\Dto\PatchIdealFractionRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Throwable;

#[OA\Patch(
    path: '/api/v1/resident-unit/{id}/ideal-fraction',
    summary: 'Update resident unit ideal fraction',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['idealFraction'],
            properties: [
                new OA\Property(property: 'idealFraction', type: 'number', format: 'float'),
            ],
        ),
    ),
    tags: ['Resident Units'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Ideal fraction updated. Empty response body.'),
        new OA\Response(response: 400, description: 'Validation error.'),
        new OA\Response(response: 404, description: 'Resident unit not found.'),
        new OA\Response(response: 409, description: 'Ideal fraction sum exceeds limit.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class ResidentUnitIdealFractionPatchController extends ApiController
{
    /**
     * @throws Throwable
     */
    public function __invoke(string $id, #[MapRequestPayload] PatchIdealFractionRequestDto $request): JsonResponse
    {
        $this->dispatch($request->toCommand($id));

        return new JsonResponse(null, Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [
            ResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
            IdealFractionSumExceedsLimitException::class => Response::HTTP_CONFLICT,
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
