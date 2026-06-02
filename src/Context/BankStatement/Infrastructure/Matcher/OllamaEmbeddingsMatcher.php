<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Matcher;

use App\Context\BankStatement\Application\Matcher\EmbeddingCandidateDto;
use App\Context\BankStatement\Application\Matcher\EmbeddingMatcherInterface;
use App\Context\BankStatement\Application\Matcher\EmbeddingVectorClientInterface;
use App\Context\BankStatement\Domain\ExpenseEmbeddingRepository;

use function array_map;
use function round;

/**
 * Semantic embedding matcher backed by pgvector.
 *
 * Flow:
 *   1. Embed the query MEMO via Ollama (1 HTTP call).
 *   2. Run a vector similarity search in expense_embedding (native pgvector <=> operator).
 *
 * Graceful degradation: returns [] on any failure so the ingestion pipeline continues.
 */
final class OllamaEmbeddingsMatcher implements EmbeddingMatcherInterface
{
    public function __construct(
        private readonly ExpenseEmbeddingRepository $embeddingRepository,
        private readonly EmbeddingVectorClientInterface $embeddingClient,
        private readonly string $embeddingModel,
    ) {}

    /**
     * @return EmbeddingCandidateDto[]
     */
    public function findSimilar(string $normalizedMemo, int $topK = 3): array
    {
        $queryVector = $this->embeddingClient->embed($normalizedMemo);

        if ($queryVector === null) {
            return [];
        }

        $rows = $this->embeddingRepository->findSimilar($queryVector, $this->embeddingModel, $topK);

        return array_map(
            fn (array $row) => new EmbeddingCandidateDto(
                candidateId: $row['expenseId'],
                label: $row['description'],
                score: round((float) $row['score'], 4),
                embeddingModel: $this->embeddingModel,
            ),
            $rows,
        );
    }
}
