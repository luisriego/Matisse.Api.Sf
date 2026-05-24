<?php

declare(strict_types=1);

namespace App\Context\Gas\Infrastructure\Http\Controller;

use App\Context\Gas\Application\UseCase\FindGasPrice\FindGasPriceQuery;
use App\Context\Gas\Domain\Exception\GasPriceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[OA\Get(
    path: '/api/v1/gas/price',
    summary: 'Get current gas price',
    tags: ['Gas'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Current gas price per m³.'),
        new OA\Response(response: 404, description: 'Gas price not found.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class FindGasPriceController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        $query = new FindGasPriceQuery();

        $priceInCents = $this->ask($query);

        return new JsonResponse(
            [
                'price_per_m3_in_cents' => $priceInCents,
            ],
            Response::HTTP_OK,
        );
    }

    public function exceptions(): array
    {
        return [
            GasPriceNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
