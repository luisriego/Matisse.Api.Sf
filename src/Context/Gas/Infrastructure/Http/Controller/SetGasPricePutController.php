<?php

declare(strict_types=1);

namespace App\Context\Gas\Infrastructure\Http\Controller;

use App\Context\Gas\Infrastructure\Http\Dto\SetGasPriceRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Throwable;

#[OA\Put(
    path: '/api/v1/gas/price/direct',
    summary: 'Set gas price directly',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['pricePerM3InCents'],
            properties: [
                new OA\Property(property: 'pricePerM3InCents', type: 'integer', description: 'Price per m³ in cents'),
            ],
        ),
    ),
    tags: ['Gas'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 201, description: 'Gas price set.'),
        new OA\Response(response: 400, description: 'Validation error.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class SetGasPricePutController extends ApiController
{
    /**
     * @throws Throwable
     */
    public function __invoke(#[MapRequestPayload] SetGasPriceRequestDto $request): JsonResponse
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
