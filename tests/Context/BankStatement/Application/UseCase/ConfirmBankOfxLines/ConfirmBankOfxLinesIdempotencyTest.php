<?php

declare(strict_types=1);

namespace App\Tests\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines;

use App\Context\Account\Domain\Account;
use App\Context\Account\Domain\AccountRepository;
use App\Context\BankStatement\Application\Service\SettlementAccountResolver;
use App\Context\BankStatement\Domain\BankTransactionImport;
use App\Context\BankStatement\Domain\BankTransactionImportRepository;
use App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines\ConfirmBankOfxLinesCommand;
use App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines\ConfirmLineDto;
use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Expense\Application\Service\ExpectedExpenseFromOfxService;
use App\Context\Expense\Application\Service\ExpectedExpenseFrequencyMapper;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\ExpenseTypeRepository;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Income\Domain\IncomeTypeRepository;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\Setup\Application\Service\SetupStatusChecker;
use App\Context\Slip\Domain\Service\SlipGenerationBreakdownBuilder;
use App\Context\Slip\Domain\SlipGenerationParameterSnapshotRepository;
use App\Context\Slip\Domain\SlipRepository;
use App\Shared\Application\RetryMechanism;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class ConfirmBankOfxLinesIdempotencyTest extends TestCase
{
    private const TEST_ACCOUNT_ID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';
    private const TEST_EXPENSE_TYPE_ID = 'e1f2a3b4-c5d6-4e7f-8a9b-0c1d2e3f4a5b';
    private const OPENING_LEDGER_ID = 'f1e2d3c4-b5a6-4f7e-8d9c-0b1a2f3e4d5c';

    /**
     * Verifies that BankTransactionImport is persisted on first confirmation.
     */
    public function testPersistsBankTransactionImportOnFirstConfirmation(): void
    {
        $dispatched = false;
        $commandBus = $this->createMock(MessageBusInterface::class);
        $commandBus->method('dispatch')
            ->willReturnCallback(function () use (&$dispatched) {
                $dispatched = true;

                return new Envelope(new \stdClass());
            });

        /** @var BankTransactionImportRepository&\PHPUnit\Framework\MockObject\MockObject $importRepo */
        $importRepo = $this->createMock(BankTransactionImportRepository::class);
        $importRepo->method('existsByImportLineKey')
            ->willReturn(false);
        $importRepo->expects(self::once())
            ->method('save')
            ->with(self::callback(function (BankTransactionImport $import): bool {
                self::assertSame('idempotent-key-001', $import->importLineKey());
                self::assertNotNull($import->expenseId());
                self::assertNull($import->incomeId());

                return true;
            }));

        $handler = $this->buildHandlerWithImportRepo(
            commandBus: $commandBus,
            retryMechanism: new RetryMechanism(maxAttempts: 3),
            bankTransactionImportRepository: $importRepo,
        );

        $command = new ConfirmBankOfxLinesCommand(
            lines: [
                new ConfirmLineDto(
                    importLineKey: 'idempotent-key-001',
                    amountInCents: 10000,
                    postedAt: '2024-01-15',
                    memo: 'Test expense',
                    accountId: self::TEST_ACCOUNT_ID,
                    dueDate: '2024-02-15',
                    lineType: ConfirmLineDto::TYPE_EXPENSE,
                    expenseTypeId: self::TEST_EXPENSE_TYPE_ID,
                    description: null,
                    recurringExpenseId: null,
                    residentUnitId: null,
                    creditKind: ConfirmLineDto::CREDIT_KIND_OTHER,
                    isExpectedExpense: false,
                    expectedExpense: null,
                ),
            ],
            bankAccountId: 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e',
        );

        $result = $handler($command);

        self::assertTrue($dispatched, 'Command bus should have been called on first confirmation');
        self::assertSame(1, $result->imported);
    }

    /**
     * Verifies that a duplicate importLineKey is skipped without re-dispatching commands.
     */
    public function testSkipsAlreadyImportedLineKeyOnDuplicateConfirmation(): void
    {
        $dispatchCount = 0;
        $commandBus = $this->createMock(MessageBusInterface::class);
        $commandBus->method('dispatch')
            ->willReturnCallback(function () use (&$dispatchCount) {
                ++$dispatchCount;

                return new Envelope(new \stdClass());
            });

        /** @var BankTransactionImportRepository&\PHPUnit\Framework\MockObject\MockObject $importRepo */
        $importRepo = $this->createMock(BankTransactionImportRepository::class);
        $importRepo->method('existsByImportLineKey')
            ->willReturn(true);

        $handler = $this->buildHandlerWithImportRepo(
            commandBus: $commandBus,
            retryMechanism: new RetryMechanism(maxAttempts: 3),
            bankTransactionImportRepository: $importRepo,
        );

        $command = new ConfirmBankOfxLinesCommand(
            lines: [
                new ConfirmLineDto(
                    importLineKey: 'idempotent-key-002',
                    amountInCents: 10000,
                    postedAt: '2024-01-15',
                    memo: 'Test expense',
                    accountId: self::TEST_ACCOUNT_ID,
                    dueDate: '2024-02-15',
                    lineType: ConfirmLineDto::TYPE_EXPENSE,
                    expenseTypeId: self::TEST_EXPENSE_TYPE_ID,
                    description: null,
                    recurringExpenseId: null,
                    residentUnitId: null,
                    creditKind: ConfirmLineDto::CREDIT_KIND_OTHER,
                    isExpectedExpense: false,
                    expectedExpense: null,
                ),
            ],
            bankAccountId: 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e',
        );

        $result = $handler($command);

        self::assertSame(0, $dispatchCount, 'Command bus should NOT be called for already-imported line');
        self::assertSame(0, $result->imported);
    }

    private function buildHandlerWithImportRepo(
        MessageBusInterface $commandBus,
        RetryMechanism $retryMechanism,
        BankTransactionImportRepository $bankTransactionImportRepository,
    ): \App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines\ConfirmBankOfxLinesCommandHandler {
        /** @var SlipRepository&\PHPUnit\Framework\MockObject\MockObject $slipRepo */
        $slipRepo = $this->createMock(SlipRepository::class);

        /** @var SlipGenerationParameterSnapshotRepository&\PHPUnit\Framework\MockObject\MockObject $snapshotRepo */
        $snapshotRepo = $this->createMock(SlipGenerationParameterSnapshotRepository::class);

        /** @var SlipGenerationBreakdownBuilder&\PHPUnit\Framework\MockObject\MockObject $breakdownBuilder */
        $breakdownBuilder = $this->createMock(SlipGenerationBreakdownBuilder::class);

        /** @var ExpenseRepository&\PHPUnit\Framework\MockObject\MockObject $expenseRepo */
        $expenseRepo = $this->createMock(ExpenseRepository::class);

        /** @var RecurringExpenseRepository&\PHPUnit\Framework\MockObject\MockObject $recurringRepo */
        $recurringRepo = $this->createMock(RecurringExpenseRepository::class);

        // Shared mocks for repositories used by multiple final services
        /** @var AccountRepository&\PHPUnit\Framework\MockObject\MockObject $accountRepo */
        $accountRepo = $this->createMock(AccountRepository::class);
        /** @var ResidentUnitRepository&\PHPUnit\Framework\MockObject\MockObject $residentUnitRepo */
        $residentUnitRepo = $this->createMock(ResidentUnitRepository::class);
        /** @var IncomeTypeRepository&\PHPUnit\Framework\MockObject\MockObject $incomeTypeRepo */
        $incomeTypeRepo = $this->createMock(IncomeTypeRepository::class);

        // Make repository mocks return empty arrays so shouldSplit() returns false
        $accountRepo->method('findAllActive')->willReturn([]);
        $accountRepo->method('findAll')->willReturn([]);
        $incomeTypeRepo->method('findAll')->willReturn([]);

        // Build SettlementAccountResolver with mocked repos — real instance since it's final
        $settlementResolver = new SettlementAccountResolver(
            accountRepository: $accountRepo,
            incomeTypeRepository: $incomeTypeRepo,
            enabled: true,
        );

        // Build SetupStatusChecker with mocked repos — real instance since it's final
        $setupChecker = $this->buildSetupStatusChecker();

        // Build ExpectedExpenseFromOfxService with mocked repos — real instance since it's final readonly
        $expectedExpenseService = $this->buildExpectedExpenseFromOfxService($recurringRepo);

        return new \App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines\ConfirmBankOfxLinesCommandHandler(
            slipRepository: $slipRepo,
            commandBus: $commandBus,
            slipGenerationParameterSnapshotRepository: $snapshotRepo,
            slipGenerationBreakdownBuilder: $breakdownBuilder,
            expenseRepository: $expenseRepo,
            recurringExpenseRepository: $recurringRepo,
            residentUnitRepository: $residentUnitRepo,
            settlementAccountResolver: $settlementResolver,
            setupStatusChecker: $setupChecker,
            accountRepository: $accountRepo,
            expectedExpenseFromOfx: $expectedExpenseService,
            retryMechanism: $retryMechanism,
            bankTransactionImportRepository: $bankTransactionImportRepository,
        );
    }

    private function buildSetupStatusChecker(): SetupStatusChecker
    {
        /** @var StoredEventRepository&\PHPUnit\Framework\MockObject\MockObject $eventRepo */
        $eventRepo = $this->createMock(StoredEventRepository::class);
        /** @var AccountRepository&\PHPUnit\Framework\MockObject\MockObject $setupAccountRepo */
        $setupAccountRepo = $this->createMock(AccountRepository::class);
        /** @var ResidentUnitRepository&\PHPUnit\Framework\MockObject\MockObject $residentUnitRepo */
        $residentUnitRepo = $this->createMock(ResidentUnitRepository::class);

        // Return empty results for most queries so setup steps are "pending" but don't crash
        $eventRepo->method('findByEventType')->willReturn([]);
        $eventRepo->method('findByEventTypesAndOccurredBetween')->willReturn([]);
        $eventRepo->method('findByEventTypesAndOccurredBetweenAndAggregateId')->willReturn([]);
        $setupAccountRepo->method('findAllActive')->willReturn([]);
        $residentUnitRepo->method('findAllActive')->willReturn([]);

        // Create a real StoredEvent for the opening reference so latestOpeningReference() returns valid data
        $openingEvent = StoredEvent::create(
            aggregateId: '00000000-0000-0000-0000-000000000001',
            eventType: \App\Context\Setup\Domain\Event\OpeningReferenceMonthWasRecorded::eventName(),
            payload: [
                'referenceMonth' => '2024-01',
                'ledgerAccountId' => self::OPENING_LEDGER_ID,
            ],
        );

        // Override the specific query to return our opening reference event
        $eventRepo->method('findByEventTypesAndOccurredBetweenAndAggregateId')
            ->willReturnCallback(function ($eventTypes) use ($openingEvent) {
                if (in_array(\App\Context\Setup\Domain\Event\OpeningReferenceMonthWasRecorded::eventName(), $eventTypes, true)) {
                    return [$openingEvent];
                }

                return [];
            });

        // Make the setup account repo return a mock Account when findOneByIdOrFail is called
        /** @var Account&\PHPUnit\Framework\MockObject\MockObject $openingAccount */
        $openingAccount = $this->createMock(Account::class);
        $openingAccount->method('id')->willReturn(self::OPENING_LEDGER_ID);
        $openingAccount->method('name')->willReturn('Conta Principal');
        $setupAccountRepo->method('findOneByIdOrFail')->willReturn($openingAccount);

        return new SetupStatusChecker(
            eventRepository: $eventRepo,
            accountRepository: $setupAccountRepo,
            residentUnitRepository: $residentUnitRepo,
        );
    }

    private function buildExpectedExpenseFromOfxService(RecurringExpenseRepository $recurringRepo): ExpectedExpenseFromOfxService
    {
        /** @var ExpenseTypeRepository&\PHPUnit\Framework\MockObject\MockObject $expenseTypeRepo */
        $expenseTypeRepo = $this->createMock(ExpenseTypeRepository::class);
        $frequencyMapper = new ExpectedExpenseFrequencyMapper();

        return new ExpectedExpenseFromOfxService(
            recurringExpenseRepository: $recurringRepo,
            expenseTypeRepository: $expenseTypeRepo,
            frequencyMapper: $frequencyMapper,
        );
    }
}
