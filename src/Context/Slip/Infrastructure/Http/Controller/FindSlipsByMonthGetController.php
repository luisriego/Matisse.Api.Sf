<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Application\UseCase\FindSlipsByMonth\FindSlipsByMonthQuery;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function array_map;
use function explode;
use function is_string;
use function preg_match;

#[OA\Get(
    path: '/api/v1/slips/by-month',
    summary: 'Find slips by target month',
    tags: ['Slips'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'targetMonth', in: 'query', required: true, schema: new OA\Schema(type: 'string', pattern: '^\d{4}-\d{2}$'),
            description: 'Target month in YYYY-MM format'),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Slips for the given month.'),
        new OA\Response(response: 400, description: 'Invalid targetMonth.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class FindSlipsByMonthGetController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $monthValue = $request->query->get('targetMonth');

        if (!is_string($monthValue) || !preg_match('/^\d{4}-\d{2}$/', $monthValue)) {
            throw new InvalidArgumentException('Invalid targetMonth. Expected YYYY-MM.');
        }

        [$year, $month] = array_map('intval', explode('-', $monthValue));

        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Invalid month. Must be between 01 and 12.');
        }

        $slips = $this->ask(new FindSlipsByMonthQuery($year, $month));

        return new JsonResponse(['slips' => $slips], Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
            \InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
