<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\FindActiveExpensesByDateRange\FindActiveExpensesByDateRangeQuery;
use App\Shared\Domain\ValueObject\DateRange;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class GetActiveExpensesByDateRangeController extends ApiController
{
    public function __invoke(int $year, int $month): JsonResponse
    {
        $dateRange = DateRange::fromMonth($year, $month);
        $query = new FindActiveExpensesByDateRangeQuery($dateRange);

        $expensesData = $this->ask($query);

        return new JsonResponse($expensesData, Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [];
    }
}
