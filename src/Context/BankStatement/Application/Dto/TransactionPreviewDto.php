<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Dto;

use App\Context\BankStatement\Application\Matcher\EmbeddingCandidateDto;

/**
 * Preview of a single bank transaction, enriched with historical matching data.
 *
 * status:
 *   - "needs_review"   : no history found (isNew=true) or low confidence → user MUST fill in.
 *   - "pre_filled"     : high-confidence match → form pre-filled from history (user can still change).
 */
final readonly class TransactionPreviewDto
{
    /**
     * @param PastAssignmentDto[]      $pastAssignments
     * @param EmbeddingCandidateDto[]  $embeddingCandidates  Top-K semantic candidates ([] when unavailable)
     */
    public function __construct(
        public readonly string $fitId,
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
    ) {}
}
