<?php

declare(strict_types=1);

namespace App\Context\Forecast\Infrastructure\Http\Controller;

use App\Context\Forecast\Application\UseCase\ListExpectedExpenses\ListExpectedExpensesQuery;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function filter_var;
use function is_string;

#[OA\Get(
    path: '/api/v1/expected-expenses',
    summary: 'List expected expenses learned from reconciliations (PREVISÃO memory)',
    tags: ['Forecast'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'year',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'integer', example: 2026),
            description: 'Filter by calendar year (same rule as GET /recurring-expenses/year/{year}).',
        ),
        new OA\Parameter(
            name: 'activeOnly',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'boolean', default: true),
        ),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Expected expense catalog for forecast.'),
        new OA\Response(response: 400, description: 'Invalid query parameters.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class ExpectedExpensesGetController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $year = null;
        if ($request->query->has('year')) {
            $rawYear = $request->query->get('year');
            if (!is_string($rawYear) && !is_int($rawYear)) {
                throw new InvalidArgumentException('year must be an integer.');
            }
            $year = (int) $rawYear;
            if ($year < 1900 || $year > 2100) {
                throw new InvalidArgumentException('year must be between 1900 and 2100.');
            }
        }

        $activeOnly = true;
        if ($request->query->has('activeOnly')) {
            $activeOnly = filter_var(
                $request->query->get('activeOnly'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE,
            );
            if ($activeOnly === null) {
                throw new InvalidArgumentException('activeOnly must be a boolean.');
            }
        }

        $data = $this->ask(new ListExpectedExpensesQuery($year, $activeOnly));

        return new JsonResponse(['data' => $data]);
    }

    public function exceptions(): array
    {
        return [
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
