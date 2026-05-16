<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Dto;

use App\Context\BankStatement\Application\Matcher\EmbeddingCandidateDto;
use OpenApi\Attributes as OA;

/**
 * Preview of a single bank transaction, enriched with historical matching data.
 *
 * status:
 *   - "needs_review"   : no history found (isNew=true) or low confidence → user MUST fill in.
 *   - "pre_filled"     : high-confidence match → form pre-filled from history (user can still change).
 */
#[OA\Schema(
    schema: 'TransactionPreview',
    properties: [
        new OA\Property(
            property: 'importLineKey',
            type: 'string',
            example: '20260310001',
            description: 'Stable key for this statement line (idempotency). Echo the value from /bank/ofx-ingest.',
        ),
        new OA\Property(property: 'bankAccountId',              type: 'string',
            description: 'OFX ACCTID from the file (not the ledger Account UUID).',
            example: '3033132774'),
        new OA\Property(property: 'type',                       type: 'string',  enum: ['DEBIT', 'CREDIT']),
        new OA\Property(property: 'amountInCents',              type: 'integer', example: 15000),
        new OA\Property(property: 'postedAt',                   type: 'string',  format: 'date', example: '2026-03-10'),
        new OA\Property(property: 'memo',                       type: 'string',  example: 'COPASA AGUA'),
        new OA\Property(property: 'status',                     type: 'string',  enum: ['needs_review', 'pre_filled']),
        new OA\Property(property: 'isNew',                      type: 'boolean'),
        new OA\Property(property: 'confidence',                 type: 'number',  format: 'float', example: 0.9),
        new OA\Property(
            property: 'pastAssignments',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PastAssignment'),
        ),
        new OA\Property(property: 'suggestedExpenseTypeId',    type: 'string',  format: 'uuid', nullable: true),
        new OA\Property(property: 'suggestedExpenseTypeName',  type: 'string',  nullable: true, example: 'Água'),
        new OA\Property(property: 'suggestedRecurringExpenseId', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'suggestedAccountId',        type: 'string',  format: 'uuid', nullable: true),
        new OA\Property(property: 'suggestedResidentUnitId',   type: 'string',  format: 'uuid', nullable: true),
        new OA\Property(
            property: 'embeddingCandidates',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/EmbeddingCandidate'),
            description: 'Top-K semantic candidates sorted by cosine score (empty = service unavailable)',
        ),
        new OA\Property(property: 'suggestedCreditKind', type: 'string', nullable: true,
            enum: ['boleto_settlement', 'other'],
            description: 'CREDIT lines only. Suggested creditKind for /bank/ofx-confirm (memo rules + income history).'),
        new OA\Property(property: 'creditClassificationSource', type: 'string', nullable: true,
            example: 'memo_pattern:other:RENDIMENTO',
            description: 'How suggestedCreditKind was inferred (null = unknown).'),
        new OA\Property(property: 'creditClassificationConfidence', type: 'number', format: 'float', example: 0.92),
        new OA\Property(property: 'suggestedIncomeTypeId', type: 'string', format: 'uuid', nullable: true,
            description: 'CREDIT lines: best guess from similar past incomes (optional).'),
        new OA\Property(property: 'suggestedIncomeTypeName', type: 'string', nullable: true),
        new OA\Property(
            property: 'pastIncomeAssignments',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/IncomePastAssignment'),
            description: 'CREDIT lines: ranked similar past incomes ([] for DEBIT lines).',
        ),
    ],
)]
final readonly class TransactionPreviewDto
{
    /**
     * @param PastAssignmentDto[]      $pastAssignments
     * @param EmbeddingCandidateDto[]  $embeddingCandidates  Top-K semantic candidates ([] when unavailable)
     * @param IncomePastAssignmentDto[] $pastIncomeAssignments
     */
    public function __construct(
        public readonly string $importLineKey,
        public readonly string $bankAccountId,
        public readonly string $type,
        public readonly int $amountInCents,
        /** ISO date Y-m-d (posting day only) */
        public readonly string $postedAt,
        public readonly string $memo,
        public readonly string $status,
        public readonly bool $isNew,
        public readonly float $confidence,
        public readonly array $pastAssignments,
        /** Best guess from top pastAssignment (null when isNew=true) */
        public readonly ?string $suggestedExpenseTypeId,
        public readonly ?string $suggestedExpenseTypeName,
        public readonly ?string $suggestedRecurringExpenseId,
        public readonly ?string $suggestedAccountId,
        public readonly ?string $suggestedResidentUnitId,
        /** Semantic embedding candidates sorted by cosine score (empty = service unavailable) */
        public readonly array $embeddingCandidates = [],
        public readonly ?string $suggestedCreditKind = null,
        public readonly ?string $creditClassificationSource = null,
        public readonly float $creditClassificationConfidence = 0.0,
        public readonly ?string $suggestedIncomeTypeId = null,
        public readonly ?string $suggestedIncomeTypeName = null,
        public readonly array $pastIncomeAssignments = [],
    ) {}
}
