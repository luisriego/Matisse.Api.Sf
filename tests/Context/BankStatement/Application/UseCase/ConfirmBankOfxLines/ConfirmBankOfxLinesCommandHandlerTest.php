<?php

declare(strict_types=1);

namespace App\Tests\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines;

use App\Context\Account\Domain\Account;
use App\Context\Account\Domain\AccountRepository;
use App\Context\BankStatement\Application\Service\SettlementAccountResolver;
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
use App\Shared\Domain\Exception\TransientException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class ConfirmBankOfxLinesCommandHandlerTest extends TestCase
{
    private const TEST_ACCOUNT_ID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';
    private const TEST_EXPENSE_TYPE_ID = 'e1f2a3b4-c5d6-4e7f-8a9b-0c1d2e3f4a5b';
    private const OPENING_LEDGER_ID = 'f1e2d3c4-b5a6-4f7e-8d9c-0b1a2f3e4d5c';

    /**
     * Verifies that the handler retries transient failures and succeeds on retry.
     */
    public function testItRetriesTransientFailuresAndSucceeds(): void
    {
        $callCount = 0;
        $commandBus = $this->createMock(MessageBusInterface::class);

        // First call throws TransientException, second call succeeds
        $commandBus->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function () use (&$callCount) {
                ++$callCount;
                if ($callCount === 1) {
                    throw new TransientException('Temporary service unavailable');
                }

                return new Envelope(new \stdClass());
            });

        $handler = $this->buildHandler(
            commandBus: $commandBus,
            retryMechanism: new RetryMechanism(maxAttempts: 3),
        );

        $command = new ConfirmBankOfxLinesCommand(
            lines: [
                new ConfirmLineDto(
                    importLineKey: 'test-key-001',
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

        self::assertSame(1, $result->imported);
    }

    /**
     * Verifies that the handler throws after exhausting all retry attempts.
     */
    public function testItThrowsAfterExhaustingAllRetries(): void
    {
        $commandBus = $this->createMock(MessageBusInterface::class);

        // Always throws TransientException
        $commandBus->method('dispatch')
            ->willReturnCallback(function () {
                throw new TransientException('Persistent transient failure');
            });

        $handler = $this->buildHandler(
            commandBus: $commandBus,
            retryMechanism: new RetryMechanism(maxAttempts: 3),
        );

        $command = new ConfirmBankOfxLinesCommand(
            lines: [
                new ConfirmLineDto(
                    importLineKey: 'test-key-002',
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

        $this->expectException(TransientException::class);
        $this->expectExceptionMessage('Persistent transient failure');

        $handler($command);
    }

    /**
     * Verifies that non-transient exceptions are propagated immediately without retry.
     */
    public function testItPropagatesNonTransientExceptionsImmediately(): void
    {
        $callCount = 0;
        $commandBus = $this->createMock(MessageBusInterface::class);

        // Throws non-transient exception — should NOT be retried
        $commandBus->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function () use (&$callCount) {
                ++$callCount;
                throw new \InvalidArgumentException('Invalid data');
            });

        $handler = $this->buildHandler(
            commandBus: $commandBus,
            retryMechanism: new RetryMechanism(maxAttempts: 3),
        );

        $command = new ConfirmBankOfxLinesCommand(
            lines: [
                new ConfirmLineDto(
                    importLineKey: 'test-key-003',
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

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data');

        $handler($command);
    }

    /**
     * @param MessageBusInterface $commandBus
     */
    private function buildHandler(
        $commandBus,
        RetryMechanism $retryMechanism,
    ): \App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines\ConfirmBankOfxLinesCommandHandler {
        $slipRepository = $this->createMock(SlipRepository::class);
        $snapshotRepo = $this->createMock(SlipGenerationParameterSnapshotRepository::class);
        $breakdownBuilder = $this->createMock(SlipGenerationBreakdownBuilder::class);
        $expenseRepo = $this->createMock(ExpenseRepository::class);
        $recurringRepo = $this->createMock(RecurringExpenseRepository::class);

        // Shared mocks for repositories used by multiple final services
        /** @var AccountRepository&\PHPUnit\Framework\MockObject\MockObject $accountRepo */
        $accountRepo = $this->createMock(AccountRepository::class);
        /** @var ResidentUnitRepository&\PHPUnit\Framework\MockObject\MockObject $residentUnitRepo */
        $residentUnitRepo = $this->createMock(ResidentUnitRepository::class);
        /** @var IncomeTypeRepository&\PHPUnit\Framework\MockObject\MockObject $incomeTypeRepo */
        $incomeTypeRepo = $this->createMock(IncomeTypeRepository::class);

        // Make repository mocks return empty arrays so shouldSplit() returns false
        // (no components match, so splitting is disabled)
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
            slipRepository: $slipRepository,
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
