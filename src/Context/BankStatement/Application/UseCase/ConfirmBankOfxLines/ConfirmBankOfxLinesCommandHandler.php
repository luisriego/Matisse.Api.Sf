<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines;

use App\Context\BankStatement\Application\Service\SettlementIncomeSplitMap;
use App\Context\BankStatement\Domain\BankTransactionImport;
use App\Context\BankStatement\Domain\BankTransactionImportRepository;
use App\Context\BankStatement\Domain\Exception\BoletoSettlementMismatchException;
use App\Context\Expense\Application\UseCase\EnterExpense\EnterExpenseCommand;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Income\Application\UseCase\EnterIncome\EnterIncomeCommand;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\Slip\Domain\Service\SlipGenerationBreakdownBuilder;
use App\Context\Slip\Domain\SlipGenerationParameterSnapshotRepository;
use App\Context\Slip\Domain\SlipRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\ValueObject\DateRange;
use DateMalformedStringException;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

use function array_filter;
use function array_flip;
use function array_map;
use function array_values;
use function count;
use function sprintf;

final class ConfirmBankOfxLinesCommandHandler implements CommandHandler
{
    private const SETTLEMENT_COMPONENT_ORDER = ['base', 'syndic', 'extra', 'reserve', 'gas'];

    /** @var array<string, string> */
    private const COMPONENT_TO_BREAKDOWN_TOTAL = [
        'base' => 'baseTotalCents',
        'syndic' => 'syndicTotalCents',
        'extra' => 'extraTotalCents',
        'reserve' => 'reserveTotalCents',
        'gas' => 'gasTotalCents',
    ];

    public function __construct(
        private readonly BankTransactionImportRepository $importRepository,
        private readonly SlipRepository $slipRepository,
        private readonly MessageBusInterface $commandBus,
        private readonly SlipGenerationParameterSnapshotRepository $slipGenerationParameterSnapshotRepository,
        private readonly SlipGenerationBreakdownBuilder $slipGenerationBreakdownBuilder,
        private readonly ExpenseRepository $expenseRepository,
        private readonly RecurringExpenseRepository $recurringExpenseRepository,
        private readonly ResidentUnitRepository $residentUnitRepository,
        private readonly SettlementIncomeSplitMap $settlementIncomeSplitMap,
        /**
         * Default income type id used for bank CREDIT lines when the client did not send one.
         * Set via environment variable DEFAULT_BANK_CREDIT_INCOME_TYPE_ID.
         * Null = the client MUST send incomeTypeId for every income line (or it will fail).
         */
        private readonly ?string $defaultCreditIncomeTypeId = null,
    ) {}

    public function __invoke(ConfirmBankOfxLinesCommand $command): ConfirmBankOfxLinesResult
    {
        $fitIds          = array_map(static fn (ConfirmLineDto $l) => $l->fitId, $command->lines);
        $alreadyImported = $this->importRepository->findImportedFitIds($command->bankAccountId, $fitIds);
        $skippedSet      = array_flip($alreadyImported);

        $fresh = array_values(array_filter(
            $command->lines,
            static fn (ConfirmLineDto $l) => !isset($skippedSet[$l->fitId]),
        ));

        $expenses       = array_values(array_filter($fresh, static fn (ConfirmLineDto $l) => !$l->isIncome()));
        $settlements    = array_values(array_filter($fresh, static fn (ConfirmLineDto $l) => $l->isBoletoSettlement()));
        $otherCredits   = array_values(array_filter($fresh, static fn (ConfirmLineDto $l) => $l->isOtherCredit()));

        $imported                           = 0;
        $consolidatedIncomeId               = null;
        $settlementMonthStr                 = null;
        $settlementExpectedSlipTotalCents   = null;
        $settlementValidatedAgainstSlips    = null;
        $settlementSplitIncomeIds           = [];

        // Validate settlement FIRST (can throw 422 when slips define a non-zero expected total).
        // Greenfield: expected slip total 0 → accept bank sum as initial consolidated income (no 422).
        if (count($settlements) > 0) {
            $settlementOutcome = $this->processSettlementBatch(
                $settlements,
                $command->bankAccountId,
                $command->settlementExtraFeePerUnitCents,
                $command->settlementReserveFundPerUnitCents,
            );
            $consolidatedIncomeId             = $settlementOutcome['incomeId'];
            $settlementMonthStr               = $settlementOutcome['month'];
            $settlementExpectedSlipTotalCents = $settlementOutcome['expectedSlipTotalCents'];
            $settlementValidatedAgainstSlips = $settlementOutcome['validatedAgainstSlips'];
            $settlementSplitIncomeIds        = $settlementOutcome['splitIncomeIds'];
            $imported += count($settlements);
        }

        foreach ($expenses as $line) {
            $expenseId = $this->dispatchExpense($line);
            $this->importRepository->save(
                new BankTransactionImport(
                    id:            Uuid::v4()->toRfc4122(),
                    fitId:         $line->fitId,
                    bankAccountId: $command->bankAccountId,
                    expenseId:     $expenseId,
                ),
                flush: false,
            );
            ++$imported;
        }

        foreach ($otherCredits as $line) {
            $incomeId = $this->dispatchIndividualIncome($line);
            $this->importRepository->save(
                new BankTransactionImport(
                    id:            Uuid::v4()->toRfc4122(),
                    fitId:         $line->fitId,
                    bankAccountId: $command->bankAccountId,
                    incomeId:      $incomeId,
                ),
                flush: false,
            );
            ++$imported;
        }

        if ($imported > 0) {
            $this->importRepository->flush();
        }

        return new ConfirmBankOfxLinesResult(
            imported:                        $imported,
            skipped:                         count($alreadyImported),
            skippedFitIds:                   array_values($alreadyImported),
            consolidatedIncomeId:            $consolidatedIncomeId,
            settlementMonth:                 $settlementMonthStr,
            settlementExpectedSlipTotalCents: $settlementExpectedSlipTotalCents,
            settlementValidatedAgainstSlips:   $settlementValidatedAgainstSlips,
            settlementSplitIncomeIds:         $settlementSplitIncomeIds,
        );
    }

    // ---------------------------------------------------------------------
    // Expense
    // ---------------------------------------------------------------------

    private function dispatchExpense(ConfirmLineDto $line): string
    {
        if ($line->expenseTypeId === null || $line->expenseTypeId === '') {
            throw new InvalidArgumentException(
                sprintf('expenseTypeId is required for expense line "%s".', $line->fitId),
            );
        }

        $expenseId = Uuid::v4()->toRfc4122();

        $this->commandBus->dispatch(new EnterExpenseCommand(
            id:             $expenseId,
            amount:         $line->amountInCents,
            type:           $line->expenseTypeId,
            accountId:      $line->accountId,
            dueDate:        $line->dueDate,
            isActive:       true,
            description:    $line->description ?? $line->memo,
            residentUnitId: $line->residentUnitId,
        ));

        return $expenseId;
    }

    // ---------------------------------------------------------------------
    // "Other" credit: one income per line, no validation.
    // ---------------------------------------------------------------------

    private function dispatchIndividualIncome(ConfirmLineDto $line): string
    {
        if ($line->incomeTypeId === null || $line->incomeTypeId === '') {
            throw new InvalidArgumentException(sprintf(
                '"other" credit line "%s" requires an explicit incomeTypeId (no default applies to non-settlement credits).',
                $line->fitId,
            ));
        }

        $incomeId = Uuid::v4()->toRfc4122();

        $postedAt = $line->postedAt !== '' ? $line->postedAt : $line->dueDate;

        $this->commandBus->dispatch(new EnterIncomeCommand(
            id:               $incomeId,
            amount:           $line->amountInCents,
            residentUnitId:   $line->residentUnitId,
            type:             $line->incomeTypeId,
            accountId:        $line->accountId,
            dueDate:          $postedAt,
            description:      $line->description ?? $line->memo,
            allowPastDueDate: true,
            paidAt:           $postedAt,
        ));

        return $incomeId;
    }

    // ---------------------------------------------------------------------
    // Boleto settlement: consolidated + validated (optionally split by slip components).
    // ---------------------------------------------------------------------

    /**
     * @param ConfirmLineDto[] $settlements
     *
     * @return array{
     *     incomeId: string,
     *     month: string,
     *     expectedSlipTotalCents: int,
     *     validatedAgainstSlips: bool,
     *     splitIncomeIds: list<array{component: string, incomeId: string, amountCents: int}>
     * }
     */
    private function processSettlementBatch(
        array $settlements,
        string $bankAccountId,
        ?int $settlementExtraFeeFallbackPerUnitCents,
        ?int $settlementReserveFundFallbackPerUnitCents,
    ): array {
        $incomeTypeId = $this->defaultCreditIncomeTypeId;
        foreach ($settlements as $line) {
            if ($line->incomeTypeId !== null && $line->incomeTypeId !== '') {
                $incomeTypeId = $line->incomeTypeId;
                break;
            }
        }

        if ($incomeTypeId === null || $incomeTypeId === '') {
            throw new InvalidArgumentException(
                'No incomeTypeId available for boleto settlement: provide it per line or configure DEFAULT_BANK_CREDIT_INCOME_TYPE_ID.',
            );
        }

        $receivedCents = 0;
        $latestPosted  = null;
        foreach ($settlements as $line) {
            $receivedCents += $line->amountInCents;
            $postedAt       = new DateTimeImmutable($line->postedAt);
            if ($latestPosted === null || $postedAt > $latestPosted) {
                $latestPosted = $postedAt;
            }
        }

        $settlementYear  = (int) $latestPosted->format('Y');
        $settlementMonth = (int) $latestPosted->format('n');

        // SlipGeneration(expenseMonth=N) saves slips with dueDate in N+1.
        // The bank credits boletos in N+1 (the slip due-date month = settlement month).
        // Therefore validation must query slips by dueDate in the settlement month itself.
        // The expense month (N) = previousMonthOf(settlement) is used only for split breakdown and description.
        $expectedCents = $this->slipRepository->sumAmountByDueDateMonth($settlementYear, $settlementMonth);

        [$expectedYear, $expectedMonth] = $this->previousMonthOf($settlementYear, $settlementMonth);

        $fitIds = array_map(static fn (ConfirmLineDto $l) => $l->fitId, $settlements);

        $validatedAgainstSlips = $expectedCents > 0;
        if ($validatedAgainstSlips && $expectedCents !== $receivedCents) {
            throw new BoletoSettlementMismatchException(
                expectedCents:   $expectedCents,
                receivedCents:   $receivedCents,
                settlementYear:  $expectedYear,
                settlementMonth: $expectedMonth,
                fitIds:          $fitIds,
            );
        }

        $paidAt          = $latestPosted->format('Y-m-d');
        $periodLabel     = sprintf('%02d/%04d', $expectedMonth, $expectedYear);
        $splitIncomeRows = [];

        if ($this->settlementIncomeSplitMap->shouldSplit()) {
            $splitIncomeRows = $this->dispatchSplitSettlementIncomes(
                $receivedCents,
                $expectedYear,
                $expectedMonth,
                $paidAt,
                $periodLabel,
                $settlementExtraFeeFallbackPerUnitCents,
                $settlementReserveFundFallbackPerUnitCents,
            );
            $primaryIncomeId = $splitIncomeRows[0]['incomeId'] ?? null;
            if ($primaryIncomeId === null) {
                throw new InvalidArgumentException('Settlement split produced no income rows.');
            }
        } else {
            $primaryIncomeId = Uuid::v4()->toRfc4122();
            $descriptionText = sprintf('Compensação de boletos — %s', $periodLabel);

            $this->commandBus->dispatch(new EnterIncomeCommand(
                id:               $primaryIncomeId,
                amount:           $receivedCents,
                residentUnitId:   null,
                type:             $incomeTypeId,
                accountId:        $settlements[0]->accountId,
                dueDate:          $paidAt,
                description:      $descriptionText,
                allowPastDueDate: true,
                paidAt:           $paidAt,
            ));
        }

        foreach ($settlements as $line) {
            $this->importRepository->save(
                new BankTransactionImport(
                    id:            Uuid::v4()->toRfc4122(),
                    fitId:         $line->fitId,
                    bankAccountId: $bankAccountId,
                    incomeId:      $primaryIncomeId,
                ),
                flush: false,
            );
        }

        return [
            'incomeId'                 => $primaryIncomeId,
            'month'                    => sprintf('%04d-%02d', $expectedYear, $expectedMonth),
            'expectedSlipTotalCents'   => $expectedCents,
            'validatedAgainstSlips'    => $validatedAgainstSlips,
            'splitIncomeIds'           => $splitIncomeRows,
        ];
    }

    /**
     * @return list<array{component: string, incomeId: string, amountCents: int}>
     */
    private function dispatchSplitSettlementIncomes(
        int $receivedCents,
        int $expenseYear,
        int $expenseMonth,
        string $paidAt,
        string $periodLabel,
        ?int $settlementExtraFeeFallbackPerUnitCents,
        ?int $settlementReserveFundFallbackPerUnitCents,
    ): array {
        $snapshot = $this->slipGenerationParameterSnapshotRepository->findByExpenseMonth($expenseYear, $expenseMonth);
        $extra    = $snapshot?->extraFeePerUnitCents() ?? $settlementExtraFeeFallbackPerUnitCents;
        $reserve  = $snapshot?->reserveFundPerUnitCents() ?? $settlementReserveFundFallbackPerUnitCents;

        if ($extra === null || $reserve === null) {
            throw new InvalidArgumentException(
                'Settlement income split is enabled but slip extra/reserve parameters are unknown for this expense month. '
                . 'Regenerate slips after upgrading, or send settlementExtraFeePerUnitCents and settlementReserveFundPerUnitCents on this confirm request.',
            );
        }

        $expenses = $this->expenseRepository->findActiveByDateRange(
            DateRange::fromMonth($expenseYear, $expenseMonth),
        );
        $recurringExpenses = $this->recurringExpenseRepository->findActiveForDateRange(
            DateRange::fromMonth($expenseYear, $expenseMonth),
        );
        $residentUnits = $this->residentUnitRepository->findAllActive();

        try {
            $breakdown = $this->slipGenerationBreakdownBuilder->build(
                $expenses,
                $recurringExpenses,
                $residentUnits,
                $expenseYear,
                $expenseMonth,
                $extra,
                $reserve,
            );
        } catch (DateMalformedStringException $e) {
            throw new InvalidArgumentException('Invalid slip breakdown date context: ' . $e->getMessage(), 0, $e);
        }

        if (isset($breakdown['error'])) {
            throw new InvalidArgumentException(
                (string) ($breakdown['message'] ?? 'Cannot compute slip breakdown for settlement split.'),
            );
        }

        /** @var array<string, int|string> $components */
        $components = $breakdown['components'];
        $amountsByKey = [];
        foreach (self::COMPONENT_TO_BREAKDOWN_TOTAL as $componentKey => $breakdownKey) {
            $amountsByKey[$componentKey] = (int) ($components[$breakdownKey] ?? 0);
        }

        $sumAllocated = (int) array_sum($amountsByKey);
        $delta         = $receivedCents - $sumAllocated;
        if ($delta !== 0) {
            $amountsByKey['base'] += $delta;
        }

        $out = [];
        foreach (self::SETTLEMENT_COMPONENT_ORDER as $componentKey) {
            $amount = $amountsByKey[$componentKey] ?? 0;
            if ($amount <= 0) {
                continue;
            }

            $map        = $this->settlementIncomeSplitMap->accountAndTypeFor($componentKey);
            $incomeId   = Uuid::v4()->toRfc4122();
            $label      = $this->componentEnglishLabel($componentKey);
            $description = sprintf('Boleto settlement — %s (%s)', $periodLabel, $label);

            $this->commandBus->dispatch(new EnterIncomeCommand(
                id:               $incomeId,
                amount:           $amount,
                residentUnitId:   null,
                type:             $map['incomeTypeId'],
                accountId:        $map['accountId'],
                dueDate:          $paidAt,
                description:      $description,
                allowPastDueDate: true,
                paidAt:           $paidAt,
            ));

            $out[] = [
                'component'    => $componentKey,
                'incomeId'     => $incomeId,
                'amountCents'  => $amount,
            ];
        }

        if ($out === []) {
            throw new InvalidArgumentException(
                'Settlement split has no positive component amounts; keep SETTLEMENT_INCOME_SPLIT_ENABLED=0 or fix slip data.',
            );
        }

        return $out;
    }

    private function componentEnglishLabel(string $componentKey): string
    {
        return match ($componentKey) {
            'base' => 'base share',
            'syndic' => 'syndic share',
            'extra' => 'extra fee',
            'reserve' => 'reserve fund',
            'gas' => 'gas',
            default => $componentKey,
        };
    }

    /**
     * @return array{0: int, 1: int} [year, month]
     */
    private function previousMonthOf(int $year, int $month): array
    {
        if ($month === 1) {
            return [$year - 1, 12];
        }

        return [$year, $month - 1];
    }
}
