<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Matcher;

use App\Context\BankStatement\Application\Dto\IncomePastAssignmentDto;
use App\Context\BankStatement\Application\Matcher\IncomeCreditHistoryMatcherInterface;
use App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines\ConfirmLineDto;
use App\Context\BankStatement\Domain\BankTransaction;
use App\Context\Income\Domain\Income;
use App\Context\Income\Domain\IncomeRepository;
use App\Shared\Domain\ValueObject\DateRange;
use App\Shared\Domain\ValueObject\DateTimeValueObject;

use function abs;
use function mb_strtoupper;
use function min;
use function round;
use function similar_text;
use function sprintf;
use function str_contains;
use function usort;

/**
 * Finds past {@see Income} records whose description resembles the bank memo (same idea as expense history).
 */
final readonly class IncomeCreditHistoryMatcher implements IncomeCreditHistoryMatcherInterface
{
    private const int HISTORY_MONTHS         = 12;
    private const int AMOUNT_TOLERANCE_CENTS = 200; // ±2 BRL — yields are tiny, settlements huge
    private const float MIN_SIMILARITY_PCT   = 38.0;

    public function __construct(
        private IncomeRepository $incomeRepository,
    ) {}

    public function match(BankTransaction $transaction): array
    {
        $fingerprint = MemoFingerprint::from($transaction->memo);
        $pastIncomes = $this->fetchIncomesInWindow($transaction->postedAt);

        $matched = [];

        foreach ($pastIncomes as $income) {
            $desc = $income->description();

            if ($desc === null || $desc === '') {
                continue;
            }

            $incomeDescFingerprint = MemoFingerprint::from($desc);
            similar_text($fingerprint, $incomeDescFingerprint, $pct);

            if ($pct < self::MIN_SIMILARITY_PCT) {
                continue;
            }

            $amountDiff  = abs($income->amount() - $transaction->absAmountInCents());
            $amountBonus = $amountDiff <= self::AMOUNT_TOLERANCE_CENTS ? 0.12 : 0.0;
            $score       = ($pct / 100.0) + $amountBonus;

            $matched[] = ['income' => $income, 'score' => $score];
        }

        if ($matched === []) {
            return ['assignments' => [], 'confidence' => 0.0, 'isNew' => true];
        }

        usort($matched, static fn (array $a, array $b) => $b['score'] <=> $a['score']);

        $assignments = [];

        foreach ($matched as $item) {
            $assignments[] = $this->toDto($item['income'], (float) $item['score']);
        }

        $topScore = (float) $matched[0]['score'];
        $capped   = min(1.0, $topScore);

        return [
            'assignments' => $assignments,
            'confidence'  => round($capped, 2),
            'isNew'       => false,
        ];
    }

    /**
     * @return Income[]
     */
    private function fetchIncomesInWindow(DateTimeValueObject $referenceDate): array
    {
        $endDate   = clone $referenceDate->toDateTime();
        $startDate = (clone $endDate)->modify(sprintf('-%d months', self::HISTORY_MONTHS));

        return $this->incomeRepository->findByDueDateInRange(
            new DateRange($startDate, $endDate),
        );
    }

    private function toDto(Income $income, float $score): IncomePastAssignmentDto
    {
        $type = $income->incomeType();

        return new IncomePastAssignmentDto(
            month: (int) $income->dueDate()->format('m'),
            year: (int) $income->dueDate()->format('Y'),
            amountInCents: $income->amount(),
            incomeTypeId: $type?->id(),
            incomeTypeName: $type?->name(),
            inferredCreditKind: $this->inferCreditKindFromDescription($income->description()),
            confidence: round(min(1.0, $score), 2),
        );
    }

    private function inferCreditKindFromDescription(?string $description): string
    {
        if ($description === null || $description === '') {
            return ConfirmLineDto::CREDIT_KIND_OTHER;
        }

        $u = mb_strtoupper($description, 'UTF-8');

        if (str_contains($u, 'COMPENSA') && str_contains($u, 'BOLET')) {
            return ConfirmLineDto::CREDIT_KIND_BOLETO_SETTLEMENT;
        }

        return ConfirmLineDto::CREDIT_KIND_OTHER;
    }
}
