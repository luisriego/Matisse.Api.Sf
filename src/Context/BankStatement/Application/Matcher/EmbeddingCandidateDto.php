<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Matcher;

use OpenApi\Attributes as OA;

/**
 * Single candidate returned by the semantic embedding matcher.
 * The candidate is an expense or expense-type that the model found
 * semantically close to the bank transaction MEMO.
 */
#[OA\Schema(
    schema: 'EmbeddingCandidate',
    properties: [
        new OA\Property(property: 'candidateId', type: 'string', format: 'uuid', description: 'ID of the matched Expense'),
        new OA\Property(property: 'label', type: 'string', example: 'COPASA água fatura mensal'),
        new OA\Property(property: 'score', type: 'number', format: 'float', example: 0.9842, description: 'Cosine similarity [0,1]'),
        new OA\Property(property: 'embeddingModel', type: 'string', example: 'nomic-embed-text'),
    ],
)]
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
