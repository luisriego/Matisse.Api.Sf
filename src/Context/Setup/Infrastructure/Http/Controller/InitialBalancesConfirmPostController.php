<?php

declare(strict_types=1);

namespace App\Context\Setup\Infrastructure\Http\Controller;

use App\Context\Setup\Application\UseCase\ConfirmInitialBalances\ConfirmInitialBalancesCommand;
use App\Shared\Domain\Exception\InvalidDataException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;

#[OA\Post(
    path: '/api/v1/setup/initial-balances/confirm',
    summary: 'Confirm and persist initial balances',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['cutoffDate', 'confirmedBankBalanceCents', 'balances', 'adjustmentPriority'],
            properties: [
                new OA\Property(property: 'cutoffDate', type: 'string', format: 'date'),
                new OA\Property(property: 'confirmedBankBalanceCents', type: 'integer'),
                new OA\Property(property: 'balances', type: 'array', items: new OA\Items(type: 'object')),
                new OA\Property(property: 'adjustmentPriority', type: 'array', items: new OA\Items(type: 'string', format: 'uuid')),
            ],
        ),
    ),
    tags: ['Setup'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 201, description: 'Initial balances confirmed.'),
        new OA\Response(response: 400, description: 'Validation error.'),
        new OA\Response(response: 404, description: 'Resource not found.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
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
