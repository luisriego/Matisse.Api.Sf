<?php

declare(strict_types=1);

namespace App\Context\Income\Infrastructure\Http\Controller;

use App\Context\Income\Application\UseCase\GetIncomeTypes\GetIncomeTypesQuery;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class GetIncomeTypesController extends ApiController
{
    /**
     * @throws Throwable
     */
    public function __invoke(): JsonResponse
    {
        $query = new GetIncomeTypesQuery();

        $incomeTypes = $this->ask($query);

        return new JsonResponse(
            $incomeTypes,
            Response::HTTP_OK,
        );
    }

    public function exceptions(): array
    {
        return [];
    }
}
