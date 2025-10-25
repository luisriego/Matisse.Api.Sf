<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Application\UseCase\EnterIncome;

use App\Context\Income\Application\UseCase\EnterIncome\EnterIncomeCommand;
use App\Context\Income\Application\UseCase\EnterIncome\EnterIncomeCommandHandler;
use App\Context\Income\Domain\Income;
use App\Context\Income\Domain\IncomeRepository;
use App\Context\Income\Domain\IncomeType;
use App\Context\Income\Domain\IncomeTypeRepository;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\Exception\DueDateMustBeInTheFutureException;
use App\Tests\Context\Income\Domain\IncomeIdMother;
use DateMalformedStringException;
use DateTime;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EnterIncomeCommandHandlerTest extends TestCase
{
    private IncomeRepository&MockObject $incomeRepository;
    private IncomeTypeRepository&MockObject $incomeTypeRepository;
    private ResidentUnitRepository&MockObject $residentUnitRepository;
    private EventBus&MockObject $eventBus;
    private EnterIncomeCommandHandler $handler;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->incomeRepository = $this->createMock(IncomeRepository::class);
        $this->incomeTypeRepository = $this->createMock(IncomeTypeRepository::class);
        $this->eventBus = $this->createMock(EventBus::class);

        $this->handler = new EnterIncomeCommandHandler(
            $this->incomeRepository,
            $this->incomeTypeRepository,
            $this->eventBus
        );
    }

    /** @test
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function test_it_enters_income_with_all_fields(): void
    {
        $incomeId = IncomeIdMother::create();
        $amount = 1500;
        $residentUnitId = 'resident-unit-id';
        $typeId = 'income-type-id';
        $dueDate = (new DateTime('+30 days'))->format('Y-m-d');
        $description = 'Monthly salary income';

        $command = new EnterIncomeCommand(
            $incomeId->value(),
            $amount,
            $residentUnitId,
            $typeId,
            $dueDate,
            $description
        );

        $incomeTypeMock = $this->createMock(IncomeType::class);

        $this->incomeTypeRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($typeId)
            ->willReturn($incomeTypeMock);

        // Track save calls with matcher
        $saveCallCount = 0;
        $this->incomeRepository
            ->expects(self::exactly(2))
            ->method('save')
            ->willReturnCallback(function(Income $income, bool $flush) use (&$saveCallCount) {
                $saveCallCount++;
                if ($saveCallCount === 1) {
                    self::assertFalse($flush, 'First save should not flush');
                } elseif ($saveCallCount === 2) {
                    self::assertTrue($flush, 'Second save should flush');
                }
            });

        $this->eventBus
            ->expects(self::once())
            ->method('publish');

        ($this->handler)($command);
    }

    /** @test
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function test_it_enters_income_without_description(): void
    {
        $incomeId = IncomeIdMother::create();
        $amount = 1000;
        $residentUnitId = 'resident-unit-id';
        $typeId = 'income-type-id';
        $dueDate = (new DateTime('+15 days'))->format('Y-m-d');

        $command = new EnterIncomeCommand(
            $incomeId->value(),
            $amount,
            $residentUnitId,
            $typeId,
            $dueDate,
            null
        );

        $incomeTypeMock = $this->createMock(IncomeType::class);

        $this->incomeTypeRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($typeId)
            ->willReturn($incomeTypeMock);

        $saveCallCount = 0;
        $this->incomeRepository
            ->expects(self::exactly(2))
            ->method('save')
            ->willReturnCallback(function(Income $income, bool $flush) use (&$saveCallCount) {
                $saveCallCount++;
                if ($saveCallCount === 1) {
                    self::assertFalse($flush);
                } elseif ($saveCallCount === 2) {
                    self::assertTrue($flush);
                }
            });

        $this->eventBus
            ->expects(self::once())
            ->method('publish');

        ($this->handler)($command);
    }

    /** @test
     * @throws Exception
     */
    public function test_it_propagates_exception_when_income_type_not_found(): void
    {
        $incomeId = IncomeIdMother::create();
        $residentUnitId = 'resident-unit-id';

        $command = new EnterIncomeCommand(
            $incomeId->value(),
            1000,
            $residentUnitId,
            'non-existent-type',
            (new DateTime('+30 days'))->format('Y-m-d'),
            'description'
        );

        $this->incomeTypeRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with('non-existent-type')
            ->willThrowException(new RuntimeException('Income type not found'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Income type not found');

        ($this->handler)($command);
    }

    /** @test
     * @throws Exception
     */
    public function test_it_propagates_date_malformed_exception(): void
    {
        $incomeId = IncomeIdMother::create();

        $command = new EnterIncomeCommand(
            $incomeId->value(),
            1000,
            'resident-unit-id',
            'type-id',
            'invalid-date',
            'description'
        );

        $incomeTypeMock = $this->createMock(IncomeType::class);

        $this->incomeTypeRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->willReturn($incomeTypeMock);

        $this->expectException(DateMalformedStringException::class);

        ($this->handler)($command);
    }

    /** @test
     * @throws Exception
     */
    public function test_it_propagates_due_date_must_be_in_future_exception(): void
    {
        $incomeId = IncomeIdMother::create();
        $pastDate = (new DateTime('-1 day'))->format('Y-m-d');

        $command = new EnterIncomeCommand(
            $incomeId->value(),
            1000,
            'resident-unit-id',
            'type-id',
            $pastDate,
            'description'
        );

        $incomeTypeMock = $this->createMock(IncomeType::class);

        $this->incomeTypeRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->willReturn($incomeTypeMock);

        $this->expectException(DueDateMustBeInTheFutureException::class);

        ($this->handler)($command);
    }

    /** @test
     * @throws Exception
     */
    public function test_it_handles_repository_save_failure(): void
    {
        $incomeId = IncomeIdMother::create();
        $amount = 1500;
        $residentUnitId = 'resident-unit-id';
        $typeId = 'income-type-id';
        $dueDate = (new DateTime('+30 days'))->format('Y-m-d');

        $command = new EnterIncomeCommand(
            $incomeId->value(),
            $amount,
            $residentUnitId,
            $typeId,
            $dueDate,
            null
        );

        $incomeTypeMock = $this->createMock(IncomeType::class);

        $this->incomeTypeRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->willReturn($incomeTypeMock);

        // Simulate repository save failure
        $this->incomeRepository
            ->expects(self::once())
            ->method('save')
            ->willThrowException(new RuntimeException('Database connection failed'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database connection failed');

        ($this->handler)($command);
    }

    /** @test
     * @throws Exception
     */
    public function test_it_handles_event_bus_publish_failure(): void
    {
        $incomeId = IncomeIdMother::create();
        $amount = 1500;
        $residentUnitId = 'resident-unit-id';
        $typeId = 'income-type-id';
        $dueDate = (new DateTime('+30 days'))->format('Y-m-d');

        $command = new EnterIncomeCommand(
            $incomeId->value(),
            $amount,
            $residentUnitId,
            $typeId,
            $dueDate,
            null
        );

        $incomeTypeMock = $this->createMock(IncomeType::class);

        $this->incomeTypeRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->willReturn($incomeTypeMock);

        $this->incomeRepository
            ->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(Income::class), false);

        // Simulate event bus failure
        $this->eventBus
            ->expects(self::once())
            ->method('publish')
            ->willThrowException(new RuntimeException('Event bus failure'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Event bus failure');

        ($this->handler)($command);
    }
}
