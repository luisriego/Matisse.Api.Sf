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
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'historyWindowMonths',
                        type: 'integer',
                        example: 12,
                        description: 'Months looked back (aligned with SQL history matchers).',
                    ),
                    new OA\Property(property: 'windowStartDate', type: 'string', format: 'date', example: '2025-04-19'),
                    new OA\Property(property: 'windowEndDate', type: 'string', format: 'date', example: '2026-04-19'),
                    new OA\Property(property: 'activeExpenseCountInWindow', type: 'integer', example: 42),
                    new OA\Property(
                        property: 'activeExpenseWithDescriptionCountInWindow',
                        type: 'integer',
                        example: 30,
                        description: 'Active expenses with memo text usable for SQL similarity matching.',
                    ),
                    new OA\Property(property: 'incomeRecordedCountInWindow', type: 'integer', example: 12),
                    new OA\Property(
                        property: 'incomeWithDescriptionCountInWindow',
                        type: 'integer',
                        example: 8,
                        description: 'Incomes with memo text usable for credit SQL similarity matching.',
                    ),
                    new OA\Property(
                        property: 'expenseEmbeddingIndexedCount',
                        type: 'integer',
                        example: 120,
                        description: 'Rows in expense_embedding (pgvector semantic debit hints).',
                    ),
                    new OA\Property(
                        property: 'debitSqlHistoryAvailable',
                        type: 'boolean',
                        description: 'True when at least one active expense with description exists in the window.',
                    ),
                    new OA\Property(property: 'debitSemanticIndexAvailable', type: 'boolean'),
                    new OA\Property(property: 'creditSqlHistoryAvailable', type: 'boolean'),
                    new OA\Property(
                        property: 'manualDebitClassificationExpected',
                        type: 'boolean',
                        description: 'True when neither SQL debit history nor semantic index can assist (typical greenfield).',
                    ),
                ],
            ),
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
