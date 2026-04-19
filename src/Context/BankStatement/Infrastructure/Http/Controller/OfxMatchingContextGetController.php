<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Http\Controller;

use App\Context\BankStatement\Application\UseCase\BankOfxMatchingContext\BankOfxMatchingContextQuery;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Get(
    path: '/api/v1/bank/ofx-matching-context',
    operationId: 'bankOfxMatchingContext',
    summary: 'DB-backed signals for OFX debit/credit matching (history + semantic index), no file upload.',
    description: 'Uses a rolling window ending on server "today" (same length as SQL history matchers). Not based on calendar month.',
    tags: ['Bank / OFX'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Counts and availability flags from the database.',
            content: new OA\JsonContent(ref: '#/components/schemas/BankOfxMatchingContext'),
        ),
    ],
)]
final class OfxMatchingContextGetController extends ApiController
{
    public function __construct(
        MessageBusInterface $commandBus,
        MessageBusInterface $queryBus,
        private readonly NormalizerInterface $normalizer,
    ) {
        parent::__construct($commandBus, $queryBus);
    }

    public function __invoke(): JsonResponse
    {
        $dto = $this->ask(new BankOfxMatchingContextQuery());

        return new JsonResponse(
            $this->normalizer->normalize($dto),
            Response::HTTP_OK,
        );
    }

    public function exceptions(): array
    {
        return [];
    }
}
