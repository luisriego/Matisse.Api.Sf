<?php

declare(strict_types=1);

namespace App\Context\Gas\Infrastructure\Http\Controller;

use App\Context\Gas\Infrastructure\Http\Dto\DefineGasPriceRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

#[OA\Put(
    path: '/api/v1/gas/price',
    summary: 'Calculate gas price from bill amount',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['billAmountInCents'],
            properties: [
                new OA\Property(property: 'billAmountInCents', type: 'integer', description: 'Gas bill amount in cents'),
                new OA\Property(property: 'cylinderCapacityInKg', type: 'integer', nullable: true),
                new OA\Property(property: 'bufferPercentage', type: 'integer', nullable: true),
            ],
        ),
    ),
    tags: ['Gas'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 201, description: 'Gas price calculated and stored.'),
        new OA\Response(response: 400, description: 'Validation error.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class CalculateGasPricePutController extends ApiController
{
    public function __invoke(#[MapRequestPayload] DefineGasPriceRequestDto $request): JsonResponse
    {
        $this->dispatch($request->toCommand());

        return new JsonResponse(null, Response::HTTP_CREATED);
    }

    public function exceptions(): array
    {
        return [
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
