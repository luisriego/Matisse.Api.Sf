<?php

declare(strict_types=1);

namespace App\Context\Gas\Infrastructure\Http\Controller;

use App\Context\Gas\Application\UseCase\FindGasReading\FindGasReadingQuery;
use App\Context\Gas\Domain\Exception\GasReadingNotFoundException;
use App\Context\ResidentUnit\Domain\ResidentUnitId; // Importar ResidentUnitId
use App\Shared\Domain\ValueObject\Month; // Importar Month
use App\Shared\Domain\ValueObject\Year; // Importar Year
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[OA\Get(
    path: '/api/v1/gas/resident-units/{id}/reading/{year}/{month}',
    summary: 'Get gas reading for resident unit and period',
    tags: ['Gas'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'year', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'month', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 12)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Gas reading for the period.'),
        new OA\Response(response: 404, description: 'Reading not found.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class FindGasReadingController extends ApiController
{
    public function __invoke(string $id, int $year, int $month): JsonResponse
    {
        $query = new FindGasReadingQuery(
            new ResidentUnitId($id),
            new Year($year),
            new Month($month),
        );

        $reading = $this->ask($query);

        return new JsonResponse(
            [
                'resident_unit_id' => $id,
                'reading' => $reading,
            ],
            Response::HTTP_OK,
        );
    }

    public function exceptions(): array
    {
        return [
            GasReadingNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
