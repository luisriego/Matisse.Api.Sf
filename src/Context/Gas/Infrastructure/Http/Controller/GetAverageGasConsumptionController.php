<?php

declare(strict_types=1);

namespace App\Context\Gas\Infrastructure\Http\Controller;

use App\Context\Gas\Application\UseCase\GetAverageGasConsumption\GetAverageGasConsumptionQuery;
use App\Context\Gas\Domain\Exception\NotEnoughReadingsException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class GetAverageGasConsumptionController extends ApiController
{
    public function __invoke(string $id): JsonResponse
    {
        try {
            $query = new GetAverageGasConsumptionQuery($id);

            $averageConsumption = $this->ask($query);

            return new JsonResponse(
                ['averageMonthlyConsumption' => $averageConsumption],
                Response::HTTP_OK,
            );
        } catch (NotEnoughReadingsException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    public function exceptions(): array
    {
        // The try-catch block now handles this, but we leave it for documentation purposes
        return [
            NotEnoughReadingsException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
