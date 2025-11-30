<?php

declare(strict_types=1);

namespace App\Context\Income\Infrastructure\Http\Controller;

use App\Context\Income\Application\UseCase\GetIncomesByMonth\GetIncomesByMonthQuery;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class GetIncomesByMonthController extends ApiController
{
    public function __invoke(int $year, int $month): JsonResponse
    {
        $query = new GetIncomesByMonthQuery($year, $month);

        $incomes = $this->ask($query);

        return new JsonResponse($incomes, Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return []; // No specific exceptions to map for this query for now
    }
}
