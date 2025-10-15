<?php

declare(strict_types=1);

namespace App\Context\Income\Infrastructure\Http\Controller;

use App\Context\Income\Application\UseCase\GetAllIncomes\GetAllIncomesQuery;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class GetAllIncomesController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        $query = new GetAllIncomesQuery();

        $incomes = $this->ask($query);

        return new JsonResponse($incomes, Response::HTTP_OK);
    }

    protected function exceptions(): array
    {
        return [];
    }
}
