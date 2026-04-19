<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines;

use App\Context\BankStatement\Domain\BankTransactionImport;
use App\Context\BankStatement\Domain\BankTransactionImportRepository;
use App\Context\BankStatement\Domain\Exception\BoletoSettlementMismatchException;
use App\Context\Expense\Application\UseCase\EnterExpense\EnterExpenseCommand;
use App\Context\Income\Application\UseCase\EnterIncome\EnterIncomeCommand;
use App\Context\Slip\Domain\SlipRepository;
use App\Shared\Application\CommandHandler;
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
    public function __construct(
        private readonly BankTransactionImportRepository $importRepository,
        private readonly SlipRepository                  $slipRepository,
        private readonly MessageBusInterface             $commandBus,
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

        // Validate settlement FIRST (can throw 422 when slips define a non-zero expected total).
        // Greenfield: expected slip total 0 → accept bank sum as initial consolidated income (no 422).
        if (count($settlements) > 0) {
            $settlementOutcome = $this->processSettlementBatch(
                $settlements,
                $command->bankAccountId,
            );
            $consolidatedIncomeId             = $settlementOutcome['incomeId'];
            $settlementMonthStr               = $settlementOutcome['month'];
            $settlementExpectedSlipTotalCents = $settlementOutcome['expectedSlipTotalCents'];
            $settlementValidatedAgainstSlips  = $settlementOutcome['validatedAgainstSlips'];
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
    // Boleto settlement: consolidated + validated.
    // ---------------------------------------------------------------------

    /**
     * @param ConfirmLineDto[] $settlements
     *
     * @return array{
     *     incomeId: string,
     *     month: string,
     *     expectedSlipTotalCents: int,
     *     validatedAgainstSlips: bool
     * }
     */
    private function processSettlementBatch(array $settlements, string $bankAccountId): array
    {
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
        [$expectedYear, $expectedMonth] = $this->previousMonthOf($settlementYear, $settlementMonth);

        $expectedCents = $this->slipRepository->sumAmountByDueDateMonth($expectedYear, $expectedMonth);

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

        $incomeId        = Uuid::v4()->toRfc4122();
        $paidAt          = $latestPosted->format('Y-m-d');
        $periodLabel     = sprintf('%02d/%04d', $expectedMonth, $expectedYear);
        $descriptionText = sprintf('Compensação de boletos — %s', $periodLabel);

        $this->commandBus->dispatch(new EnterIncomeCommand(
            id:               $incomeId,
            amount:           $receivedCents,
            residentUnitId:   null,
            type:             $incomeTypeId,
            accountId:        $settlements[0]->accountId,
            dueDate:          $paidAt,
            description:      $descriptionText,
            allowPastDueDate: true,
            paidAt:           $paidAt,
        ));

        foreach ($settlements as $line) {
            $this->importRepository->save(
                new BankTransactionImport(
                    id:            Uuid::v4()->toRfc4122(),
                    fitId:         $line->fitId,
                    bankAccountId: $bankAccountId,
                    incomeId:      $incomeId,
                ),
                flush: false,
            );
        }

        return [
            'incomeId'                 => $incomeId,
            'month'                    => sprintf('%04d-%02d', $expectedYear, $expectedMonth),
            'expectedSlipTotalCents'   => $expectedCents,
            'validatedAgainstSlips'    => $validatedAgainstSlips,
        ];
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
