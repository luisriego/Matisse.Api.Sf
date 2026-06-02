<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Matcher;

use App\Context\BankStatement\Application\Dto\PastAssignmentDto;
use App\Context\BankStatement\Application\Matcher\ExpenseHistoryMatcherInterface;
use App\Context\BankStatement\Domain\BankTransaction;
use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Shared\Domain\ValueObject\DateRange;
use App\Shared\Domain\ValueObject\DateTimeValueObject;

use function abs;
use function min;
use function round;
use function similar_text;
use function sprintf;
use function usort;

/**
 * Queries expense history to find previous assignments for a given bank transaction.
 *
 * Returns a list of PastAssignmentDto sorted by most-recent first, plus a confidence score.
 */
final readonly class ExpenseHistoryMatcher implements ExpenseHistoryMatcherInterface
{
    private const int HISTORY_MONTHS = 12;
    private const int AMOUNT_TOLERANCE_CENTS = 500; // ±5 BRL
    private const float HIGH_CONFIDENCE_THRESHOLD = 0.75;

    public function __construct(
        private ExpenseRepository $expenseRepository,
    ) {}

    /**
     * @return array{assignments: PastAssignmentDto[], confidence: float, isNew: bool}
     */
    public function match(BankTransaction $transaction): array
    {
        $fingerprint = MemoFingerprint::from($transaction->memo);
        $pastExpenses = $this->fetchExpensesInWindow($transaction->postedAt);

        $matched = [];

        foreach ($pastExpenses as $expense) {
            $expenseFingerprint = MemoFingerprint::from($expense->description() ?? '');
            similar_text($fingerprint, $expenseFingerprint, $pct);

            if ($pct < 40.0) {
                continue;
            }

            // Bonus if amounts are similar
            $amountDiff = abs($expense->amount() - $transaction->absAmountInCents());
            $amountBonus = $amountDiff <= self::AMOUNT_TOLERANCE_CENTS ? 0.15 : 0.0;

            $score = ($pct / 100.0) + $amountBonus;
            $matched[] = ['expense' => $expense, 'score' => $score];
        }

        if (empty($matched)) {
            return ['assignments' => [], 'confidence' => 0.0, 'isNew' => true];
        }

        usort($matched, static fn (array $a, array $b) => $b['score'] <=> $a['score']);

        $assignments = [];

        foreach ($matched as $item) {
            $assignments[] = $this->toDto($item['expense'], (float) $item['score']);
        }

        $topScore = (float) $matched[0]['score'];
        $capped   = min(1.0, $topScore);

        return [
            'assignments' => $assignments,
            'confidence'  => round($capped, 2),
            'isNew'       => false,
        ];
    }

    public function isHighConfidence(float $confidence): bool
    {
        return $confidence >= self::HIGH_CONFIDENCE_THRESHOLD;
    }

    /**
     * @return Expense[]
     */
    private function fetchExpensesInWindow(DateTimeValueObject $referenceDate): array
    {
        $endDate   = clone $referenceDate->toDateTime();
        $startDate = (clone $endDate)->modify(sprintf('-%d months', self::HISTORY_MONTHS));

        return $this->expenseRepository->findActiveByDateRange(
            new DateRange($startDate, $endDate),
        );
    }

    private function toDto(Expense $expense, float $score): PastAssignmentDto
    {
        $recurring = $expense->recurringExpense();

        return new PastAssignmentDto(
            month: (int) $expense->dueDate()->format('m'),
            year: (int) $expense->dueDate()->format('Y'),
            amountInCents: $expense->amount(),
            expenseTypeId: $expense->type()?->id(),
            expenseTypeName: $expense->type()?->name(),
            recurringExpenseId: $recurring?->id(),
            recurringExpenseName: $recurring?->description(),
            accountId: $expense->account()?->id(),
            residentUnitId: $expense->residentUnitId(),
            confidence: $score,
        );
    }
}
