<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Domain;

use DateTimeImmutable;

/**
 * Stores the pre-computed embedding vector for an Expense description.
 *
 * One row per expense (upsert on re-index).
 * The vector is produced by an LLM embedding model (e.g. nomic-embed-text via Ollama).
 */
class ExpenseEmbedding
{
    private DateTimeImmutable $indexedAt;

    public function __construct(
        private readonly string $id,
        private readonly string $expenseId,
        /** @var float[] */
        private readonly array $vector,
        private readonly string $description,
        private readonly string $embeddingModel,
    ) {
        $this->indexedAt = new DateTimeImmutable();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function expenseId(): string
    {
        return $this->expenseId;
    }

    /**
     * @return float[]
     */
    public function vector(): array
    {
        return $this->vector;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function embeddingModel(): string
    {
        return $this->embeddingModel;
    }

    public function indexedAt(): DateTimeImmutable
    {
        return $this->indexedAt;
    }
}
