<?php

declare(strict_types=1);

namespace App\Context\Gas\Infrastructure\Http\Controller;

use App\Context\Gas\Application\UseCase\FindGasReading\FindGasReadingQuery;
use App\Context\Gas\Domain\Exception\GasReadingNotFoundException;
use App\Context\ResidentUnit\Domain\ResidentUnitId; // Importar ResidentUnitId
use App\Shared\Domain\ValueObject\Month; // Importar Month
use App\Shared\Domain\ValueObject\Year; // Importar Year
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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

    protected function exceptions(): array
    {
        return [
            GasReadingNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
