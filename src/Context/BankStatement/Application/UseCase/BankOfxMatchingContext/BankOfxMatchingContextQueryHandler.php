<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\UseCase\BankOfxMatchingContext;

use App\Context\BankStatement\Application\Dto\BankOfxMatchingContextDto;
use App\Context\BankStatement\Domain\ExpenseEmbeddingRepository;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Income\Domain\IncomeRepository;
use App\Shared\Application\QueryHandler;
use App\Shared\Domain\ValueObject\DateRange;
use DateTime;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use function sprintf;

/**
 * Window length matches expense/income SQL history matchers (12 months);
 * reference date is "today" (server) because no statement posting date exists yet.
 */
#[AsMessageHandler(bus: 'query.bus')]
final readonly class BankOfxMatchingContextQueryHandler implements QueryHandler
{
    public function __construct(
        private ExpenseRepository $expenseRepository,
        private IncomeRepository $incomeRepository,
        private ExpenseEmbeddingRepository $expenseEmbeddingRepository,
    ) {}

    public function __invoke(BankOfxMatchingContextQuery $_query): BankOfxMatchingContextDto
    {
        $historyMonths = 12;
        $endDate   = new DateTime('today');
        $endDate->setTime(23, 59, 59);
        $startDate = (clone $endDate)->modify(sprintf('-%d months', $historyMonths));
        $range     = new DateRange($startDate, $endDate);

        $activeExpenseCount                    = $this->expenseRepository->countActiveInDueDateRange($range);
        $activeExpenseWithDescriptionCount    = $this->expenseRepository->countActiveWithNonEmptyDescriptionInDueDateRange($range);
        $incomeCount                          = $this->incomeRepository->countInDueDateRange($range);
        $incomeWithDescriptionCount           = $this->incomeRepository->countWithNonEmptyDescriptionInDueDateRange($range);
        $embeddingCount                       = $this->expenseEmbeddingRepository->countIndexed();

        $debitSql     = $activeExpenseWithDescriptionCount > 0;
        $debitSemantic = $embeddingCount > 0;
        $creditSql    = $incomeWithDescriptionCount > 0;

        return new BankOfxMatchingContextDto(
            historyWindowMonths: $historyMonths,
            windowStartDate: $range->startDate()->format('Y-m-d'),
            windowEndDate: $range->endDate()->format('Y-m-d'),
            activeExpenseCountInWindow: $activeExpenseCount,
            activeExpenseWithDescriptionCountInWindow: $activeExpenseWithDescriptionCount,
            incomeRecordedCountInWindow: $incomeCount,
            incomeWithDescriptionCountInWindow: $incomeWithDescriptionCount,
            expenseEmbeddingIndexedCount: $embeddingCount,
            debitSqlHistoryAvailable: $debitSql,
            debitSemanticIndexAvailable: $debitSemantic,
            creditSqlHistoryAvailable: $creditSql,
            manualDebitClassificationExpected: !$debitSql && !$debitSemantic,
        );
    }
}
