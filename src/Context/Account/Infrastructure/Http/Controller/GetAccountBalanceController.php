<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Application\UseCase\GetAccountBalance\GetAccountBalanceQuery;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GetAccountBalanceController extends ApiController
{
    /**
     * @throws DateMalformedStringException
     */
    #[Route('/accounts/{id}/balance', name: 'get_account_balance', methods: ['GET'])]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $upToDate = $request->query->get('upToDate');
        $upToDate = $upToDate ? new DateTimeImmutable($upToDate) : null;

        $query = new GetAccountBalanceQuery($id, $upToDate);

        $balance = $this->ask($query);

        return new JsonResponse(
            [
                'account_id' => $id,
                'balance' => $balance,
                'up_to_date' => $upToDate ? $upToDate->format('Y-m-d') : (new DateTimeImmutable())->format('Y-m-d'),
            ],
            Response::HTTP_OK,
        );
    }

    protected function exceptions(): array
    {
        return [
            ResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
