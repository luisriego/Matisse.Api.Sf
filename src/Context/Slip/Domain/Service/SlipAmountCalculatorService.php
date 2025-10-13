<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Service;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use Psr\Log\LoggerInterface;

use function round;
use function sprintf;

readonly class SlipAmountCalculatorService
{
    public function __construct(private LoggerInterface $logger) {}

    public function calculate(
        ResidentUnit $resident,
        int $totalEquallyDividedExpensesInCents,
        int $totalFractionBasedExpensesInCents,
        int $numberOfPayingResidents,
    ): int {
        $residentSlipAmountInCents = 0;

        if ($numberOfPayingResidents > 0 && $totalEquallyDividedExpensesInCents > 0) {
            $amountPerResidentEqual = (int) round($totalEquallyDividedExpensesInCents / $numberOfPayingResidents);
            $residentSlipAmountInCents += $amountPerResidentEqual;
            $this->logger->info(sprintf(
                '[SlipAmountCalculator]    + (Unit: %s) Equally divided expenses: %.2f (Total: %.2f / %d residents)',
                $resident->unit(),
                $amountPerResidentEqual / 100,
                $totalEquallyDividedExpensesInCents / 100,
                $numberOfPayingResidents,
            ));
        }

        $idealFraction = $resident->idealFraction();

        if ($idealFraction > 0 && $totalFractionBasedExpensesInCents > 0) {
            $shareOfFractionExpenses = (int) round($totalFractionBasedExpensesInCents * $idealFraction);
            $residentSlipAmountInCents += $shareOfFractionExpenses;
            $this->logger->info(sprintf(
                '[SlipAmountCalculator]    + (Unit: %s) Fraction-based expenses (%.2f%% of Total %.2f): %.2f',
                $resident->unit(),
                $idealFraction * 100,
                $totalFractionBasedExpensesInCents / 100,
                $shareOfFractionExpenses / 100,
            ));
        }

        return $residentSlipAmountInCents;
    }
}
