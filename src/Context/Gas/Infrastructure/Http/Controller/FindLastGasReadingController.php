<?php

declare(strict_types=1);

namespace App\Context\Gas\Infrastructure\Http\Controller;

use App\Context\Gas\Application\UseCase\FindLastGasReading\FindLastGasReadingQuery;
use App\Context\Gas\Domain\Exception\GasReadingNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class FindLastGasReadingController extends ApiController
{
    public function __invoke(string $id): JsonResponse
    {
        $query = new FindLastGasReadingQuery($id);

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
