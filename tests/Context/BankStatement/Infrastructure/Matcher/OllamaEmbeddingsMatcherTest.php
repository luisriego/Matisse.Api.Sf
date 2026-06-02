<?php

declare(strict_types=1);

namespace App\Tests\Context\BankStatement\Infrastructure\Matcher;

use App\Context\BankStatement\Application\Matcher\EmbeddingCandidateDto;
use App\Context\BankStatement\Application\Matcher\EmbeddingVectorClientInterface;
use App\Context\BankStatement\Domain\ExpenseEmbeddingRepository;
use App\Context\BankStatement\Infrastructure\Matcher\OllamaEmbeddingsMatcher;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for OllamaEmbeddingsMatcher (pgvector-backed).
 * Both ExpenseEmbeddingRepository and EmbeddingVectorClientInterface are mocked —
 * no database / HTTP / Ollama connection required.
 */
final class OllamaEmbeddingsMatcherTest extends TestCase
{
    private const string MODEL = 'nomic-embed-text';

    public function testItReturnsEmptyArrayWhenOllamaReturnsNull(): void
    {
        $embeddingRepo = $this->createMock(ExpenseEmbeddingRepository::class);
        $embeddingRepo->expects(self::never())->method('findSimilar');

        $client = $this->createMock(EmbeddingVectorClientInterface::class);
        $client->method('embed')->willReturn(null);

        $matcher = new OllamaEmbeddingsMatcher($embeddingRepo, $client, self::MODEL);

        self::assertSame([], $matcher->findSimilar('copasa agua factura'));
    }

    public function testItReturnsCandidatesFromDbSortedByScore(): void
    {
        $queryVector = [1.0, 0.0, 0.0];

        $dbRows = [
            ['expenseId' => 'uuid-aaa', 'description' => 'COPASA água fatura mensal', 'score' => 0.9921],
            ['expenseId' => 'uuid-bbb', 'description' => 'CEMIG energia eletrica', 'score' => 0.6543],
        ];

        $embeddingRepo = $this->createMock(ExpenseEmbeddingRepository::class);
        $embeddingRepo
            ->expects(self::once())
            ->method('findSimilar')
            ->with($queryVector, self::MODEL, 2)
            ->willReturn($dbRows);

        $client = $this->createMock(EmbeddingVectorClientInterface::class);
        $client->expects(self::once())->method('embed')->willReturn($queryVector);

        $matcher = new OllamaEmbeddingsMatcher($embeddingRepo, $client, self::MODEL);
        $results  = $matcher->findSimilar('copasa agua', topK: 2);

        self::assertCount(2, $results);
        self::assertInstanceOf(EmbeddingCandidateDto::class, $results[0]);
        self::assertSame('uuid-aaa', $results[0]->candidateId);
        self::assertSame(0.9921, $results[0]->score);
        self::assertSame('uuid-bbb', $results[1]->candidateId);
        self::assertSame(0.6543, $results[1]->score);
    }

    public function testItPassesTopKToRepository(): void
    {
        $queryVector = [0.5, 0.5, 0.0];

        $embeddingRepo = $this->createMock(ExpenseEmbeddingRepository::class);
        $embeddingRepo
            ->expects(self::once())
            ->method('findSimilar')
            ->with($queryVector, self::MODEL, 5)
            ->willReturn([]);

        $client = $this->createMock(EmbeddingVectorClientInterface::class);
        $client->method('embed')->willReturn($queryVector);

        $matcher = new OllamaEmbeddingsMatcher($embeddingRepo, $client, self::MODEL);
        $results  = $matcher->findSimilar('gasto generico', topK: 5);

        self::assertSame([], $results);
    }

    public function testItReturnsEmptyArrayWhenDbHasNoResults(): void
    {
        $queryVector = [0.1, 0.2, 0.7];

        $embeddingRepo = $this->createMock(ExpenseEmbeddingRepository::class);
        $embeddingRepo->method('findSimilar')->willReturn([]);

        $client = $this->createMock(EmbeddingVectorClientInterface::class);
        $client->method('embed')->willReturn($queryVector);

        $matcher = new OllamaEmbeddingsMatcher($embeddingRepo, $client, self::MODEL);

        self::assertSame([], $matcher->findSimilar('sin historial'));
    }
}
