<?php

declare(strict_types=1);

namespace App\Tests\Context\BankStatement\Infrastructure\Matcher;

use App\Context\BankStatement\Application\Matcher\EmbeddingCandidateDto;
use App\Context\BankStatement\Application\Matcher\EmbeddingVectorClientInterface;
use App\Context\BankStatement\Infrastructure\Matcher\OllamaEmbeddingsMatcher;
use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseRepository;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for OllamaEmbeddingsMatcher.
 * {@see EmbeddingVectorClientInterface} is mocked — no HTTP / Ollama required.
 */
final class OllamaEmbeddingsMatcherTest extends TestCase
{
    private const string MODEL = 'nomic-embed-text';

    public function test_it_returns_empty_array_when_corpus_is_empty(): void
    {
        $repository = $this->createMock(ExpenseRepository::class);
        $repository->method('findActiveByDateRange')->willReturn([]);

        $client = $this->createMock(EmbeddingVectorClientInterface::class);
        $client->expects(self::never())->method('embed');

        $matcher = new OllamaEmbeddingsMatcher($repository, $client, self::MODEL);

        $result = $matcher->findSimilar('copasa agua factura');

        self::assertSame([], $result);
    }

    public function test_it_returns_candidates_sorted_by_score(): void
    {
        $expenseA = $this->buildExpenseMock('uuid-aaa', 'COPASA água fatura mensal');
        $expenseB = $this->buildExpenseMock('uuid-bbb', 'CEMIG energia eletrica');

        $repository = $this->createMock(ExpenseRepository::class);
        $repository->method('findActiveByDateRange')->willReturn([$expenseA, $expenseB]);

        $call = 0;
        $client = $this->createMock(EmbeddingVectorClientInterface::class);
        $client->method('embed')->willReturnCallback(function () use (&$call) {
            ++$call;

            return match ($call) {
                1       => [1.0, 0.0, 0.0], // query memo
                2       => [1.0, 0.0, 0.0], // corpus A — identical direction → cosine 1
                3       => [0.0, 1.0, 0.0], // corpus B — orthogonal → cosine 0
                default => null,
            };
        });

        $matcher = new OllamaEmbeddingsMatcher($repository, $client, self::MODEL);
        $results = $matcher->findSimilar('copasa agua', topK: 2);

        self::assertCount(2, $results);
        self::assertInstanceOf(EmbeddingCandidateDto::class, $results[0]);
        self::assertSame('uuid-aaa', $results[0]->candidateId);
        self::assertSame(1.0, $results[0]->score);
        self::assertSame('uuid-bbb', $results[1]->candidateId);
        self::assertSame(0.0, $results[1]->score);
    }

    public function test_it_limits_to_topK(): void
    {
        $expenses = array_map(
            fn (int $i) => $this->buildExpenseMock("uuid-{$i}", "Gasto {$i}"),
            range(1, 10),
        );

        $repository = $this->createMock(ExpenseRepository::class);
        $repository->method('findActiveByDateRange')->willReturn($expenses);

        $client = $this->createMock(EmbeddingVectorClientInterface::class);
        $client->method('embed')->willReturn([1.0, 0.0, 0.0]);

        $matcher = new OllamaEmbeddingsMatcher($repository, $client, self::MODEL);
        $results = $matcher->findSimilar('gasto generico', topK: 3);

        self::assertCount(3, $results);
    }

    public function test_it_returns_empty_array_when_embedding_client_returns_null(): void
    {
        $expense = $this->buildExpenseMock('uuid-x', 'Algún gasto');

        $repository = $this->createMock(ExpenseRepository::class);
        $repository->method('findActiveByDateRange')->willReturn([$expense]);

        $client = $this->createMock(EmbeddingVectorClientInterface::class);
        $client->method('embed')->willReturn(null);

        $matcher = new OllamaEmbeddingsMatcher($repository, $client, self::MODEL);

        self::assertSame([], $matcher->findSimilar('copasa agua'));
    }

    // --- helpers ---

    private function buildExpenseMock(string $id, string $description): Expense
    {
        $expense = $this->createMock(Expense::class);
        $expense->method('id')->willReturn($id);
        $expense->method('description')->willReturn($description);

        return $expense;
    }
}
