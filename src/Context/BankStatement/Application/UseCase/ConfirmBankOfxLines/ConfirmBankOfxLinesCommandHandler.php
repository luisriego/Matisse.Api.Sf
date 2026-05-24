<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines;

use App\Context\Account\Domain\Account;
use App\Context\Account\Domain\AccountRepository;
use App\Context\BankStatement\Application\Dto\ExpectedExpenseCreateOrUpdateDto;
use App\Context\BankStatement\Application\Dto\ExpectedExpenseSpecDto;
use App\Context\BankStatement\Application\Service\SettlementAccountResolver;
use App\Context\BankStatement\Domain\Exception\BoletoSettlementMismatchException;
use App\Context\Expense\Application\Service\ExpectedExpenseFromOfxService;
use App\Context\Expense\Application\UseCase\EnterExpense\EnterExpenseCommand;
use App\Context\Expense\Application\UseCase\EnterExpense\EnterMonthlyRecurringExpenseCommand;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Income\Application\UseCase\EnterIncome\EnterIncomeCommand;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\Setup\Application\Service\SetupStatusChecker;
use App\Context\Slip\Domain\Service\SlipGenerationBreakdownBuilder;
use App\Context\Slip\Domain\SlipGenerationParameterSnapshotRepository;
use App\Context\Slip\Domain\SlipRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Domain\ValueObject\DateRange;
use DateMalformedStringException;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

use function array_filter;
use function array_values;
use function count;
use function preg_match;
use function sprintf;
use function trim;

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
        private readonly SlipRepository $slipRepository,
        private readonly MessageBusInterface $commandBus,
        private readonly SlipGenerationParameterSnapshotRepository $slipGenerationParameterSnapshotRepository,
        private readonly SlipGenerationBreakdownBuilder $slipGenerationBreakdownBuilder,
        private readonly ExpenseRepository $expenseRepository,
        private readonly RecurringExpenseRepository $recurringExpenseRepository,
        private readonly ResidentUnitRepository $residentUnitRepository,
        private readonly SettlementAccountResolver $settlementAccountResolver,
        private readonly SetupStatusChecker $setupStatusChecker,
        private readonly AccountRepository $accountRepository,
        private readonly ExpectedExpenseFromOfxService $expectedExpenseFromOfx,
        /**
         * Default income type id used for bank CREDIT lines when the client did not send one.
         * Set via environment variable DEFAULT_BANK_CREDIT_INCOME_TYPE_ID.
         * Null = the client MUST send incomeTypeId for every income line (or it will fail).
         */
        private readonly ?string $defaultCreditIncomeTypeId = null,
        /**
         * Optional ledger Account UUID when credit lines omit accountId and setup has no ledgerAccountId.
         * Env: DEFAULT_BANK_LEDGER_ACCOUNT_ID. If unset, prefers a "principal" account by name; auxiliary/gas accounts are last resort.
         */
        private readonly ?string $defaultBankLedgerAccountId = null,
    ) {}

    public function __invoke(ConfirmBankOfxLinesCommand $command): ConfirmBankOfxLinesResult
    {
        $lines = $command->lines;

        $expenses       = array_values(array_filter($lines, static fn (ConfirmLineDto $l) => !$l->isIncome()));
        $settlements    = array_values(array_filter($lines, static fn (ConfirmLineDto $l) => $l->isBoletoSettlement()));
        $otherCredits   = array_values(array_filter($lines, static fn (ConfirmLineDto $l) => $l->isOtherCredit()));

        $imported                           = 0;
        $consolidatedIncomeId               = null;
        $settlementMonthStr                 = null;
        $settlementExpectedSlipTotalCents   = null;
        $settlementValidatedAgainstSlips    = null;
        $settlementSplitIncomeIds           = [];
        $expectedExpensesLinked             = 0;
        $expectedExpensesCreated            = 0;

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
            $outcome = $this->dispatchExpense($line, $command->bankAccountId);
            if ($outcome['linked']) {
                ++$expectedExpensesLinked;
            }
            if ($outcome['created']) {
                ++$expectedExpensesCreated;
            }
            ++$imported;
        }

        foreach ($otherCredits as $line) {
            $this->dispatchIndividualIncome($line, $command->bankAccountId);
            ++$imported;
        }

        return new ConfirmBankOfxLinesResult(
            imported:                        $imported,
            consolidatedIncomeId:            $consolidatedIncomeId,
            settlementMonth:                 $settlementMonthStr,
            settlementExpectedSlipTotalCents: $settlementExpectedSlipTotalCents,
            settlementValidatedAgainstSlips:   $settlementValidatedAgainstSlips,
            settlementSplitIncomeIds:         $settlementSplitIncomeIds,
            expectedExpensesLinked:           $expectedExpensesLinked,
            expectedExpensesCreated:          $expectedExpensesCreated,
        );
    }

    // ---------------------------------------------------------------------
    // Expense
    // ---------------------------------------------------------------------

    /**
     * @return array{linked: bool, created: bool}
     *
     * @throws DateMalformedStringException
     */
    private function dispatchExpense(ConfirmLineDto $line, string $bankAccountId): array
    {
        if ($line->expenseTypeId === null || $line->expenseTypeId === '') {
            throw new InvalidArgumentException(
                'Cada línea de gasto debe incluir expenseTypeId.',
            );
        }

        $accountId = $this->resolveLedgerAccountId($line->accountId, $bankAccountId);

        $expenseId = Uuid::v4()->toRfc4122();

        if (!$line->isExpectedExpense) {
            $this->commandBus->dispatch(new EnterExpenseCommand(
                id:             $expenseId,
                amount:         $line->amountInCents,
                type:           $line->expenseTypeId,
                accountId:      $accountId,
                dueDate:        $line->dueDate,
                isActive:       true,
                description:    $line->description ?? $line->memo,
                residentUnitId: $line->residentUnitId,
            ));

            return ['linked' => false, 'created' => false];
        }

        $spec = $line->expectedExpense;
        if ($spec === null && ($line->recurringExpenseId === null || $line->recurringExpenseId === '')) {
            $spec = new ExpectedExpenseSpecDto(
                null,
                new ExpectedExpenseCreateOrUpdateDto(
                    displayName: $line->description ?? $line->memo,
                    frequency: 'monthly',
                    amountKind: 'variable',
                    dueDay: (int) (new DateTimeImmutable($line->dueDate))->format('j'),
                ),
            );
        } elseif ($spec === null) {
            $spec = new ExpectedExpenseSpecDto($line->recurringExpenseId, null);
        }

        $outcome = $this->expectedExpenseFromOfx->upsertFromOfxLine(
            isExpectedExpense: true,
            spec: $spec,
            legacyRecurringExpenseId: $line->recurringExpenseId,
            expenseTypeId: $line->expenseTypeId,
            accountId: $accountId,
            amountInCents: $line->amountInCents,
            dueDate: $line->dueDate,
            memo: $line->memo,
            description: $line->description,
        );

        $this->commandBus->dispatch(new EnterMonthlyRecurringExpenseCommand(
            $expenseId,
            $outcome['recurringExpenseId'],
            $accountId,
            $line->amountInCents,
            $line->dueDate,
        ));

        return [
            'linked' => $outcome['linked'],
            'created' => $outcome['created'],
        ];
    }

    // ---------------------------------------------------------------------
    // "Other" credit: one income per line, no validation.
    // ---------------------------------------------------------------------

    private function dispatchIndividualIncome(ConfirmLineDto $line, string $importBankAccountId): void
    {
        if ($line->incomeTypeId === null || $line->incomeTypeId === '') {
            throw new InvalidArgumentException(
                'Las líneas de crédito tipo «otro» requieren incomeTypeId.',
            );
        }

        $incomeId = Uuid::v4()->toRfc4122();

        $postedAt = $line->postedAt !== '' ? $line->postedAt : $line->dueDate;
        $accountId = $this->resolveLedgerAccountId($line->accountId, $importBankAccountId);

        $this->commandBus->dispatch(new EnterIncomeCommand(
            id:               $incomeId,
            amount:           $line->amountInCents,
            residentUnitId:   $line->residentUnitId,
            type:             $line->incomeTypeId,
            accountId:        $accountId,
            dueDate:          $postedAt,
            description:      $line->description ?? $line->memo,
            allowPastDueDate: true,
            paidAt:           $postedAt,
        ));
    }

    /**
     * Cuenta del razón para movimiento bancario genérico: UUID en línea → env → setup (si no es cuenta gas/auxiliar) →
     * plan de cuentas (nombre «principal» primero, gas/reserva/síndico al final) → último recurso ledger del setup → bank UUID.
     */
    private function resolveLedgerAccountId(string $lineAccountId, string $importBankAccountId): string
    {
        $t = trim($lineAccountId);
        if ($t !== '' && Uuid::isValid($t)) {
            return $t;
        }

        $default = trim((string) ($this->defaultBankLedgerAccountId ?? ''));
        if ($default !== '' && Uuid::isValid($default)) {
            return $default;
        }

        $openingLedger = $this->openingReferenceLedgerAccountId();
        if ($openingLedger !== null) {
            try {
                $openingAccount = $this->accountRepository->findOneByIdOrFail($openingLedger);
                if ($this->accountDefaultBookingTier($openingAccount) < 2) {
                    return $openingLedger;
                }
            } catch (ResourceNotFoundException) {
            }
        }

        $fromChart = $this->pickPreferredDefaultLedgerAccountId();
        if ($fromChart !== null) {
            return $fromChart;
        }

        if ($openingLedger !== null) {
            return $openingLedger;
        }

        $b = trim($importBankAccountId);
        if ($b !== '' && Uuid::isValid($b)) {
            return $b;
        }

        throw new InvalidArgumentException(
            'No hay cuentas en el razón; cree al menos una o defina DEFAULT_BANK_LEDGER_ACCOUNT_ID.',
        );
    }

    private function openingReferenceLedgerAccountId(): ?string
    {
        $opening = $this->setupStatusChecker->status()['openingReference'] ?? null;
        if (!\is_array($opening)) {
            return null;
        }
        $ledgerId = trim((string) ($opening['ledgerAccountId'] ?? ''));
        if ($ledgerId === '' || !Uuid::isValid($ledgerId)) {
            return null;
        }

        return $ledgerId;
    }

    /**
     * Elige cuenta por defecto: nombres que sugieren «principal / caja corriente» primero; gas, reserva, síndico, etc. al final.
     */
    private function pickPreferredDefaultLedgerAccountId(): ?string
    {
        $accounts = $this->accountRepository->findAllActive();
        if ($accounts === []) {
            $accounts = $this->accountRepository->findAll();
        }
        if ($accounts === []) {
            return null;
        }
        usort($accounts, fn (Account $a, Account $b): int => $this->compareAccountsForDefaultBankBooking($a, $b));

        return $accounts[0]->id();
    }

    private function compareAccountsForDefaultBankBooking(Account $a, Account $b): int
    {
        return [
            $this->accountDefaultBookingTier($a),
            strtolower((string) $a->name()),
            $a->id(),
        ] <=> [
            $this->accountDefaultBookingTier($b),
            strtolower((string) $b->name()),
            $b->id(),
        ];
    }

    /**
     * 0 = cuenta principal (por nombre); 1 = genérica; 2 = gas / reserva / síndico / extra — último recurso para ingresos genéricos.
     */
    private function accountDefaultBookingTier(Account $account): int
    {
        $n = strtolower((string) $account->name());

        if (preg_match(
            '/conta\s+principal|cuenta\s+principal|caja\s+corriente|conta\s+corrente|\bprincipal\b|^principal$|main\s+account|cc\s+banco/i',
            $n,
        ) === 1) {
            return 0;
        }

        if (preg_match(
            '/\b(gas|gás|gnv|gravame|reserva|fundo\s+de\s+reserva|syndic|s[ií]ndico|taxa\s+extra|quota\s+extra)\b/i',
            $n,
        ) === 1) {
            return 2;
        }

        return 1;
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
                'Liquidação de boletos: falta incomeTypeId (por linha) ou variável DEFAULT_BANK_CREDIT_INCOME_TYPE_ID no servidor.',
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

        $validatedAgainstSlips = $expectedCents > 0;
        if ($validatedAgainstSlips && $expectedCents !== $receivedCents) {
            throw new BoletoSettlementMismatchException(
                expectedCents:   $expectedCents,
                receivedCents:   $receivedCents,
                settlementYear:  $expectedYear,
                settlementMonth: $expectedMonth,
            );
        }

        $paidAt          = $latestPosted->format('Y-m-d');
        $periodLabel     = sprintf('%02d/%04d', $expectedMonth, $expectedYear);
        $splitIncomeRows = [];

        if ($this->settlementAccountResolver->shouldSplit()) {
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
                throw new InvalidArgumentException('Divisão de liquidação: não foi gerada nenhuma linha de ingresso.');
            }
        } else {
            $primaryIncomeId = Uuid::v4()->toRfc4122();
            $descriptionText = sprintf('Compensação de boletos — %s', $periodLabel);

            $this->commandBus->dispatch(new EnterIncomeCommand(
                id:               $primaryIncomeId,
                amount:           $receivedCents,
                residentUnitId:   null,
                type:             $incomeTypeId,
                accountId:        $this->resolveLedgerAccountId(
                    $settlements[0]->accountId,
                    $bankAccountId,
                ),
                dueDate:          $paidAt,
                description:      $descriptionText,
                allowPastDueDate: true,
                paidAt:           $paidAt,
            ));
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
                'Divisão de liquidação ativa: faltam parâmetros de taxa extra/reserva para o mês de despesa. '
                . 'Gere novamente os boletos ou envie settlementExtraFeePerUnitCents e settlementReserveFundPerUnitCents neste confirm.',
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
            throw new InvalidArgumentException('Data inválida no breakdown de boletos: ' . $e->getMessage(), 0, $e);
        }

        if (isset($breakdown['error'])) {
            throw new InvalidArgumentException(
                (string) ($breakdown['message'] ?? 'Não foi possível calcular o breakdown para a divisão da liquidação.'),
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

            $map        = $this->settlementAccountResolver->accountAndTypeFor($componentKey);
            $incomeId   = Uuid::v4()->toRfc4122();
            $label      = $this->componentLabelPtBr($componentKey);
            $description = sprintf('Liquidação de boletos — %s (%s)', $periodLabel, $label);

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
                'Divisão da liquidação sem valores positivos; desative SETTLEMENT_INCOME_SPLIT_ENABLED ou corrija dados dos boletos.',
            );
        }

        return $out;
    }

    private function componentLabelPtBr(string $componentKey): string
    {
        return match ($componentKey) {
            'base'    => 'cota base',
            'syndic'  => 'quota síndico',
            'extra'   => 'taxa extra',
            'reserve' => 'fundo de reserva',
            'gas'     => 'gás',
            default   => $componentKey,
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
