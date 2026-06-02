<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Matcher;

/**
 * Port for turning text into an embedding vector (Ollama, local model, etc.).
 *
 * @phpstan-return list<float>|null
 */
interface EmbeddingVectorClientInterface
{
    /**
     * @return list<float>|null
     */
    public function embed(string $text): ?array;
}
