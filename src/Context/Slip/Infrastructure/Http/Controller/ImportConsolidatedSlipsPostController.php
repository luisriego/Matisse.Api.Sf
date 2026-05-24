<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Domain\Exception\PeriodAlreadyClosedException;
use App\Context\Slip\Infrastructure\Http\Dto\ImportConsolidatedSlipsRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[OA\Post(
    path: '/api/v1/slips/import',
    summary: 'Import consolidated slips for a month',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['targetMonth', 'slips'],
            properties: [
                new OA\Property(property: 'targetMonth', type: 'string', pattern: '^\d{4}-\d{2}$', example: '2026-03'),
                new OA\Property(
                    property: 'slips',
                    type: 'array',
                    items: new OA\Items(
                        required: ['residentUnitId', 'amountCents'],
                        properties: [
                            new OA\Property(property: 'residentUnitId', type: 'string', format: 'uuid'),
                            new OA\Property(property: 'amountCents', type: 'integer'),
                            new OA\Property(property: 'components', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'integer'), nullable: true),
                        ],
                    ),
                ),
            ],
        ),
    ),
    tags: ['Slips'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 201, description: 'Consolidated slips imported.'),
        new OA\Response(response: 400, description: 'Validation error.'),
        new OA\Response(response: 404, description: 'Resource not found.'),
        new OA\Response(response: 409, description: 'Period already closed.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class ImportConsolidatedSlipsPostController extends ApiController
{
    public function __invoke(ImportConsolidatedSlipsRequestDto $request): JsonResponse
    {
        $this->dispatch($request->toCommand());

        return new JsonResponse(
            ['message' => 'Consolidated slips imported successfully.'],
            Response::HTTP_CREATED,
        );
    }

    public function exceptions(): array
    {
        return [
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
            ResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
            PeriodAlreadyClosedException::class => Response::HTTP_CONFLICT,
        ];
    }
}
