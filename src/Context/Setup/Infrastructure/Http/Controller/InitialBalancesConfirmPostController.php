<?php

declare(strict_types=1);

namespace App\Context\Setup\Infrastructure\Http\Controller;

use App\Context\Setup\Application\UseCase\ConfirmInitialBalances\ConfirmInitialBalancesCommand;
use App\Shared\Domain\Exception\InvalidDataException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;

final class InitialBalancesConfirmPostController extends ApiController
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

        $command = new ConfirmInitialBalancesCommand(
            (string) $data['cutoffDate'],
            (int) $data['confirmedBankBalanceCents'],
            $data['balances'],
            $data['adjustmentPriority'],
        );

        $this->dispatch($command);

        return new JsonResponse(['message' => 'Saldos iniciales confirmados.'], Response::HTTP_CREATED);
    }

    public function exceptions(): array
    {
        return [
            InvalidDataException::class      => Response::HTTP_BAD_REQUEST,
            ResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
