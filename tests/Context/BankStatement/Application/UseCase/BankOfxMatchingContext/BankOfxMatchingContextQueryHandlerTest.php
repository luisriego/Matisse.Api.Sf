<?php

declare(strict_types=1);

namespace App\Tests\Context\BankStatement\Application\UseCase\BankOfxMatchingContext;

use App\Context\BankStatement\Application\UseCase\BankOfxMatchingContext\BankOfxMatchingContextQuery;
use App\Context\BankStatement\Application\UseCase\BankOfxMatchingContext\BankOfxMatchingContextQueryHandler;
use App\Context\BankStatement\Domain\ExpenseEmbeddingRepository;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Income\Domain\IncomeRepository;
use PHPUnit\Framework\TestCase;

final class BankOfxMatchingContextQueryHandlerTest extends TestCase
{
    public function testItMarksManualDebitWhenNoHistoryAndNoEmbeddings(): void
    {
        $expenseRepo = $this->createMock(ExpenseRepository::class);
        $expenseRepo->method('countActiveInDueDateRange')->willReturn(0);
        $expenseRepo->method('countActiveWithNonEmptyDescriptionInDueDateRange')->willReturn(0);

        $incomeRepo = $this->createMock(IncomeRepository::class);
        $incomeRepo->method('countInDueDateRange')->willReturn(0);
        $incomeRepo->method('countWithNonEmptyDescriptionInDueDateRange')->willReturn(0);

        $embeddingRepo = $this->createMock(ExpenseEmbeddingRepository::class);
        $embeddingRepo->method('countIndexed')->willReturn(0);

        $handler = new BankOfxMatchingContextQueryHandler($expenseRepo, $incomeRepo, $embeddingRepo);
        $dto     = $handler(new BankOfxMatchingContextQuery());

        self::assertSame(12, $dto->historyWindowMonths);
        self::assertFalse($dto->debitSqlHistoryAvailable);
        self::assertFalse($dto->debitSemanticIndexAvailable);
        self::assertFalse($dto->creditSqlHistoryAvailable);
        self::assertTrue($dto->manualDebitClassificationExpected);
    }

    public function testItDetectsDebitSqlHistory(): void
    {
        $expenseRepo = $this->createMock(ExpenseRepository::class);
        $expenseRepo->method('countActiveInDueDateRange')->willReturn(3);
        $expenseRepo->method('countActiveWithNonEmptyDescriptionInDueDateRange')->willReturn(2);

        $incomeRepo = $this->createMock(IncomeRepository::class);
        $incomeRepo->method('countInDueDateRange')->willReturn(0);
        $incomeRepo->method('countWithNonEmptyDescriptionInDueDateRange')->willReturn(0);

        $embeddingRepo = $this->createMock(ExpenseEmbeddingRepository::class);
        $embeddingRepo->method('countIndexed')->willReturn(0);

        $handler = new BankOfxMatchingContextQueryHandler($expenseRepo, $incomeRepo, $embeddingRepo);
        $dto     = $handler(new BankOfxMatchingContextQuery());

        self::assertTrue($dto->debitSqlHistoryAvailable);
        self::assertFalse($dto->debitSemanticIndexAvailable);
        self::assertFalse($dto->manualDebitClassificationExpected);
    }

    public function testItDetectsSemanticIndexWithoutSql(): void
    {
        $expenseRepo = $this->createMock(ExpenseRepository::class);
        $expenseRepo->method('countActiveInDueDateRange')->willReturn(0);
        $expenseRepo->method('countActiveWithNonEmptyDescriptionInDueDateRange')->willReturn(0);

        $incomeRepo = $this->createMock(IncomeRepository::class);
        $incomeRepo->method('countInDueDateRange')->willReturn(0);
        $incomeRepo->method('countWithNonEmptyDescriptionInDueDateRange')->willReturn(0);

        $embeddingRepo = $this->createMock(ExpenseEmbeddingRepository::class);
        $embeddingRepo->method('countIndexed')->willReturn(5);

        $handler = new BankOfxMatchingContextQueryHandler($expenseRepo, $incomeRepo, $embeddingRepo);
        $dto     = $handler(new BankOfxMatchingContextQuery());

        self::assertFalse($dto->debitSqlHistoryAvailable);
        self::assertTrue($dto->debitSemanticIndexAvailable);
        self::assertFalse($dto->manualDebitClassificationExpected);
    }
}
