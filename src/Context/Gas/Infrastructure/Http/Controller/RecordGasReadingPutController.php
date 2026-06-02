<?php

declare(strict_types=1);

namespace App\Context\Gas\Infrastructure\Http\Controller;

use App\Context\Gas\Infrastructure\Http\Dto\RecordGasReadingRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use DateMalformedStringException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

#[OA\Put(
    path: '/api/v1/gas/reading',
    summary: 'Record gas meter reading for a resident unit',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['id', 'residentUnitId', 'year', 'month', 'reading'],
            properties: [
                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'residentUnitId', type: 'string', format: 'uuid'),
                new OA\Property(property: 'year', type: 'integer', example: 2026),
                new OA\Property(property: 'month', type: 'integer', minimum: 1, maximum: 12),
                new OA\Property(property: 'reading', type: 'number', format: 'float'),
            ],
        ),
    ),
    tags: ['Gas'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 201, description: 'Gas reading recorded.'),
        new OA\Response(response: 400, description: 'Validation error.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class RecordGasReadingPutController extends ApiController
{
    public function __invoke(#[MapRequestPayload] RecordGasReadingRequestDto $dto): JsonResponse
    {
        $this->dispatch($dto->toCommand());

        return new JsonResponse(null, Response::HTTP_CREATED);
    }

    public function exceptions(): array
    {
        return [
            DateMalformedStringException::class => Response::HTTP_BAD_REQUEST,
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
