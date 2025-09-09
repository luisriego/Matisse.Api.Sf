<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Service;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\Slip\Domain\Slip;
use App\Context\Slip\Domain\ValueObject\SlipAmount;
use App\Context\Slip\Domain\ValueObject\SlipDueDate;
use App\Context\Slip\Domain\ValueObject\SlipId;
use App\Shared\Domain\ValueObject\Uuid;
use DateMalformedStringException;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

use function count;
use function sprintf;

readonly class SlipFactory
{
    public function __construct(
        private MonthlyExpenseAggregatorService $monthlyExpenseAggregator,
        private SlipAmountCalculatorService $slipAmountCalculator,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param ResidentUnit[] $residentUnits
     *
     * @return Slip[]
     *
     * @throws DateMalformedStringException
     */
    public function createFromExpensesAndUnits(array $allExpenses, array $residentUnits, int $expenseYear, int $expenseMonth): array
    {
        if (empty($residentUnits) || empty($allExpenses)) {
            return [];
        }

        // 1. Aggregate expenses using the dedicated service
        $expenseTotals = $this->monthlyExpenseAggregator->aggregateTotals($allExpenses);
        $totalEquallyDividedExpenses = $expenseTotals['equal'];
        $totalFractionBasedExpenses = $expenseTotals['fraction'];
        $numberOfPayingResidents = count($residentUnits);

        $this->logger->info(sprintf(
            'Aggregated totals for %d-%d: Equal: %.2f, Fraction: %.2f, Individual: %.2f',
            $expenseYear,
            $expenseMonth,
            $expenseTotals['equal'] / 100,
            $expenseTotals['fraction'] / 100,
            $expenseTotals['individual'] / 100,
        ));

        // 2. Prepare Due Date (logic remains the same)
        $dueDateContext = (new DateTimeImmutable(sprintf('%d-%d-01', $expenseYear, $expenseMonth)))->modify('+1 month');
        $dueYear = (int) $dueDateContext->format('Y');
        $dueMonth = (int) $dueDateContext->format('m');
        $dueDateTime = SlipDueDate::selectDueDate($dueYear, $dueMonth);
        $dueDate = new SlipDueDate($dueDateTime);

        $slips = [];

        // 3. Iterate over residents, calculate amount for each, and create Slip
        foreach ($residentUnits as $residentUnit) {
            $amountInCents = $this->slipAmountCalculator->calculate(
                $residentUnit,
                $totalEquallyDividedExpenses,
                $totalFractionBasedExpenses,
                $numberOfPayingResidents,
            );

            if ($amountInCents <= 0) {
                $this->logger->info(sprintf(
                    'Calculated amount for unit %s is zero or negative. Skipping slip creation.',
                    $residentUnit->unit(),
                ));

                continue;
            }

            $id = new SlipId(Uuid::random()->value());
            $slipAmount = new SlipAmount($amountInCents);
            $slips[] = Slip::createForUnit($id, $slipAmount, $residentUnit, $dueDate);
        }

        return $slips;
    }
}
