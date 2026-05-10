<?php

declare(strict_types=1);

namespace App\Context\Ledger\Infrastructure\Http\Controller;

use App\Context\Ledger\Application\UseCase\FindLedgerMovements\FindLedgerMovementsQuery;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class LedgerMovementsGetController extends ApiController
{
    public function __invoke(int $year, int $month, Request $request): JsonResponse
    {
        $accountId = $request->query->get('accountId');
        $query     = new FindLedgerMovementsQuery(
            $year,
            $month,
            is_string($accountId) && $accountId !== '' ? $accountId : null,
        );

        return new JsonResponse($this->ask($query), Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [];
    }
}
