<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Matcher;

use App\Context\BankStatement\Application\Matcher\EmbeddingCandidateDto;
use App\Context\BankStatement\Application\Matcher\EmbeddingMatcherInterface;
use App\Context\BankStatement\Application\Matcher\EmbeddingVectorClientInterface;
use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Shared\Domain\ValueObject\DateRange;
use DateTime;

use function array_slice;
use function sqrt;
use function usort;

/**
 * Semantic embedding matcher: embeds MEMO + corpus via {@see EmbeddingVectorClientInterface},
 * ranks by cosine similarity, returns top-K.
 *
 * On any embedding failure, returns [] (graceful degradation).
 */
final class OllamaEmbeddingsMatcher implements EmbeddingMatcherInterface
{
    private const int HISTORY_MONTHS = 12;
    private const int CORPUS_MAX     = 200;

    public function __construct(
        private readonly ExpenseRepository $expenseRepository,
        private readonly EmbeddingVectorClientInterface $embeddingClient,
        private readonly string $embeddingModel,
    ) {}

    /**
     * @return EmbeddingCandidateDto[]
     */
    public function findSimilar(string $normalizedMemo, int $topK = 3): array
    {
        $corpus = $this->buildCorpus();

        if (empty($corpus)) {
            return [];
        }

        $queryVector = $this->embeddingClient->embed($normalizedMemo);

        if ($queryVector === null) {
            return [];
        }

        $scored = [];

        foreach ($corpus as ['id' => $id, 'label' => $label, 'text' => $text]) {
            $corpusVector = $this->embeddingClient->embed($text);

            if ($corpusVector === null) {
                continue;
            }

            $score = $this->cosine($queryVector, $corpusVector);

            $scored[] = new EmbeddingCandidateDto(
                candidateId: $id,
                label: $label,
                score: round($score, 4),
                embeddingModel: $this->embeddingModel,
            );
        }

        usort($scored, static fn (EmbeddingCandidateDto $a, EmbeddingCandidateDto $b) => $b->score <=> $a->score);

        return array_slice($scored, 0, $topK);
    }

    /**
     * @return array<array{id: string, label: string, text: string}>
     */
    private function buildCorpus(): array
    {
        $endDate   = new DateTime();
        $startDate = (clone $endDate)->modify(sprintf('-%d months', self::HISTORY_MONTHS));
        $dateRange = new DateRange($startDate, $endDate);

        $expenses = $this->expenseRepository->findActiveByDateRange($dateRange);

        $corpus = [];

        foreach (array_slice($expenses, 0, self::CORPUS_MAX) as $expense) {
            $description = $expense->description();

            if ($description === null || $description === '') {
                continue;
            }

            $corpus[] = [
                'id'    => $expense->id(),
                'label' => $description,
                'text'  => MemoFingerprint::from($description),
            ];
        }

        return $corpus;
    }

    /**
     * @param list<float> $a
     * @param list<float> $b
     */
    private function cosine(array $a, array $b): float
    {
        $dot   = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $val) {
            $dot   += $val * ($b[$i] ?? 0.0);
            $normA += $val * $val;
            $normB += ($b[$i] ?? 0.0) * ($b[$i] ?? 0.0);
        }

        $denom = sqrt($normA) * sqrt($normB);

        if ($denom === 0.0) {
            return 0.0;
        }

        return max(0.0, min(1.0, $dot / $denom));
    }
}
