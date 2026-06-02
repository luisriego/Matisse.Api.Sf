<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Domain;

interface ExpenseEmbeddingRepository
{
    /**
     * Persist (insert or replace) an embedding.
     */
    public function upsert(ExpenseEmbedding $embedding): void;

    /**
     * Find the K nearest neighbours to the given query vector using cosine similarity.
     *
     * @param float[] $queryVector
     *
     * @return array<array{expenseId: string, description: string, score: float}>
     */
    public function findSimilar(array $queryVector, string $embeddingModel, int $topK = 3): array;

    /**
     * Delete all embeddings for a given expense (e.g. before re-indexing).
     */
    public function deleteByExpenseId(string $expenseId): void;

    /**
     * Total number of indexed embeddings (useful for health-check / admin).
     */
    public function countIndexed(): int;
}
