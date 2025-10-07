<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Service;

use App\Context\Condominium\Domain\Service\CondominiumFundAmountService;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeRepository;
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
        private StoredEventRepository $storedEventRepository,
        private LoggerInterface $logger,
        private ExpenseTypeRepository $expenseTypeRepository,
        private CondominiumFundAmountService $condominiumFundAmountService,
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

        $dueDateContext = (new DateTimeImmutable(sprintf('%d-%d-01', $expenseYear, $expenseMonth)))->modify('+1 month');
        $dueYear = (int) $dueDateContext->format('Y');
        $dueMonth = (int) $dueDateContext->format('m');
        $dueDateTime = SlipDueDate::selectDueDate($dueYear, $dueMonth);
        $dueDate = new SlipDueDate($dueDateTime);

        $slips = [];

        $previousMonth = (new DateTimeImmutable(sprintf('%d-%d-01', $expenseYear, $expenseMonth)))->modify('-1 month');
        $gasExpensesByUnit = $this->getGasExpensesForMonth($previousMonth->format('Y'), $previousMonth->format('m'));

        // Obtener la configuración de fondos para la fecha de generación del slip (o mes anterior)
        $fundConfigurationDate = new DateTimeImmutable(sprintf('%d-%d-01', $expenseYear, $expenseMonth));
        $condominiumConfiguration = $this->condominiumFundAmountService->getActiveConfigurationForDate($fundConfigurationDate);
        $reserveFundAmount = $condominiumConfiguration->reserveFundAmount();
        $constructionFundAmount = $condominiumConfiguration->constructionFundAmount();

        foreach ($residentUnits as $residentUnit) {
            $mainAccountAmount = $this->slipAmountCalculator->calculate(
                $residentUnit,
                $totalEquallyDividedExpenses,
                $totalFractionBasedExpenses,
                $numberOfPayingResidents,
            );

            $residentUnitId = $residentUnit->id();
            $gasAmount = $gasExpensesByUnit[$residentUnitId] ?? 0;

            $totalAmountInCents = $mainAccountAmount + $gasAmount + $reserveFundAmount + $constructionFundAmount;

            if ($totalAmountInCents <= 0) {
                $this->logger->info(sprintf(
                    'Calculated amount for unit %s is zero or negative. Skipping slip creation.',
                    $residentUnit->unit(),
                ));

                continue;
            }

            $id = new SlipId(Uuid::random()->value());
            $slipAmount = new SlipAmount($totalAmountInCents);
            $slips[] = Slip::createForUnit(
                $id,
                $slipAmount,
                $residentUnit,
                $dueDate,
                $mainAccountAmount,
                $gasAmount,
                $reserveFundAmount,
                $constructionFundAmount,
            );
        }

        return $slips;
    }

    /**
     * @throws DateMalformedStringException
     */
    private function getGasExpensesForMonth(string $year, string $month): array
    {
        $gasExpenses = [];

        $startDate = new DateTimeImmutable(sprintf('%s-%s-01 00:00:00', $year, $month));
        $endDate = $startDate->modify('last day of this month 23:59:59');

        $events = $this->storedEventRepository->findByEventNamesAndOccurredBetween(
            ['expense.entered', 'expense.compensated'],
            $startDate,
            $endDate,
        );

        $gasTypeId = $this->resolveGasExpenseTypeId();

        foreach ($events as $event) {
            $payload = $event->toPrimitives();

            if (isset($payload['body']['type']) && $payload['body']['type'] === $gasTypeId) {
                $residentUnitId = $payload['body']['residentUnitId'] ?? null;
                $amount = $payload['body']['amount'] ?? 0;

                if ($residentUnitId) {
                    if (!isset($gasExpenses[$residentUnitId])) {
                        $gasExpenses[$residentUnitId] = 0;
                    }
                    $gasExpenses[$residentUnitId] += $amount;
                }
            }
        }

        return $gasExpenses;
    }

    private function resolveGasExpenseTypeId(): string
    {
        /** @var ExpenseType $type */
        $type = $this->expenseTypeRepository->findOneByCodeOrFail('SP3GA');

        return $type->id();
    }
}
