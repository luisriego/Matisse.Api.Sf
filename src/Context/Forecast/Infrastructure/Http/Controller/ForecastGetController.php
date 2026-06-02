<?php

declare(strict_types=1);

namespace App\Context\Forecast\Infrastructure\Http\Controller;

use App\Context\Forecast\Application\UseCase\GetForecast\GetForecastQuery;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function is_string;
use function preg_match;

#[OA\Get(
    path: '/api/v1/forecast/{targetMonth}',
    summary: 'Monthly expense forecast (PREVISÃO) — projection only, no accounting entries',
    tags: ['Forecast'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'targetMonth',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string', example: '2022-09'),
        ),
        new OA\Parameter(
            name: 'reconciliationMonth',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'string', example: '2022-08'),
            description: 'Last reconciled month used as reference for variable amounts. Defaults to targetMonth - 1.',
        ),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Forecast projection.'),
        new OA\Response(response: 400, description: 'Invalid targetMonth.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class ForecastGetController extends ApiController
{
    public function __invoke(string $targetMonth, Request $request): JsonResponse
    {
        if (1 !== preg_match('/^\d{4}-\d{2}$/', $targetMonth)) {
            throw new InvalidArgumentException('Invalid targetMonth. Expected YYYY-MM.');
        }

        $reconciliationMonth = $request->query->get('reconciliationMonth');

        if ($reconciliationMonth !== null && (!is_string($reconciliationMonth) || 1 !== preg_match('/^\d{4}-\d{2}$/', $reconciliationMonth))) {
            throw new InvalidArgumentException('Invalid reconciliationMonth. Expected YYYY-MM.');
        }

        $payload = $this->ask(new GetForecastQuery(
            $targetMonth,
            is_string($reconciliationMonth) ? $reconciliationMonth : null,
        ));

        if (isset($payload['error'])) {
            return new JsonResponse($payload, Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['data' => $payload]);
    }

    public function exceptions(): array
    {
        return [
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
            \InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
