<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Matcher;

/**
 * Port for semantic embedding-based candidate retrieval.
 *
 * Implementations compute an embedding for the given MEMO text, compare it
 * against a corpus of expense descriptions and return the top-K closest entries.
 *
 * Contract:
 *  - NEVER auto-applies or persists anything.
 *  - On infrastructure errors (timeout, model not found, etc.) MUST return [].
 *  - Scores are normalised cosine similarities in [0.0, 1.0].
 */
interface EmbeddingMatcherInterface
{
    /**
     * @param string $normalizedMemo  Output of MemoFingerprint::from() or raw memo text.
     * @param int    $topK            Number of candidates to return (default 3).
     *
     * @return EmbeddingCandidateDto[]  Sorted descending by score; empty on failure.
     */
    public function findSimilar(string $normalizedMemo, int $topK = 3): array;
}
