<?php

declare(strict_types=1);

namespace App\Context\Setup\Infrastructure\Http\Controller;

use App\Context\Setup\Application\UseCase\PreviewInitialBalances\PreviewInitialBalancesQuery;
use App\Shared\Domain\Exception\InvalidDataException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;

final class InitialBalancesPreviewPostController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (
            !isset($data['cutoffDate'], $data['confirmedBankBalanceCents'], $data['balances'], $data['adjustmentPriority'])
            || !is_array($data['balances'])
            || !is_array($data['adjustmentPriority'])
        ) {
            throw new InvalidDataException(
                'Required fields: cutoffDate, confirmedBankBalanceCents, balances (array), adjustmentPriority (array of accountIds).',
            );
        }

        $query = new PreviewInitialBalancesQuery(
            (string) $data['cutoffDate'],
            (int) $data['confirmedBankBalanceCents'],
            $data['balances'],
            $data['adjustmentPriority'],
        );

        return new JsonResponse($this->ask($query), Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [
            InvalidDataException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
