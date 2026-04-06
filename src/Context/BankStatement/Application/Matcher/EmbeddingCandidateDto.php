<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Matcher;

/**
 * Single candidate returned by the semantic embedding matcher.
 * The candidate is an expense or expense-type that the model found
 * semantically close to the bank transaction MEMO.
 */
final readonly class EmbeddingCandidateDto
{
    public function __construct(
        /** ID of the matched Expense or ExpenseType */
        public readonly string $candidateId,
        /** Human-readable label for traceability */
        public readonly string $label,
        /** Cosine similarity in [0, 1] */
        public readonly float $score,
        /** Ollama model used to produce the embedding (for traceability) */
        public readonly string $embeddingModel,
    ) {}
}
