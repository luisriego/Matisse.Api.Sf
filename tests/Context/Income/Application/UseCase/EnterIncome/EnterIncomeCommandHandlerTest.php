<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Application\UseCase\EnterIncome;

use App\Context\Account\Domain\AccountRepository;
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
use App\Tests\Context\Account\Domain\AccountIdMother;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Income\Domain\IncomeIdMother;
use App\Tests\Shared\Domain\UuidMother;
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
    private AccountRepository&MockObject $accountRepository;
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
        $this->residentUnitRepository = $this->createMock(ResidentUnitRepository::class);
        $this->accountRepository = $this->createMock(AccountRepository::class);
        $this->eventBus = $this->createMock(EventBus::class);

        $this->handler = new EnterIncomeCommandHandler(
            $this->incomeRepository,
            $this->incomeTypeRepository,
            $this->residentUnitRepository,
            $this->accountRepository,
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
        $accountId = UuidMother::create();
        $dueDate = (new DateTime('+30 days'))->format('Y-m-d');
        $description = 'Monthly salary income';

        $command = new EnterIncomeCommand(
            $incomeId->value(),
            $amount,
            $residentUnitId,
            $typeId,
            $accountId,
            $dueDate,
            $description
        );

        $residentUnitMock = $this->createMock(ResidentUnit::class);
        $residentUnitMock->method('id')->willReturn($residentUnitId);
        $incomeTypeMock = $this->createMock(IncomeType::class);

        $this->residentUnitRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($residentUnitId)
            ->willReturn($residentUnitMock);

        $this->incomeTypeRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($typeId)
            ->willReturn($incomeTypeMock);

        $this->accountRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($accountId)
            ->willReturn(AccountMother::create(AccountIdMother::create($accountId)));

        $this->incomeRepository
            ->expects(self::once())
            ->method('save')
            ->with($this->callback(function (Income $income) {
                $events = $income->pullDomainEvents();
                return count($events) === 1; // It has IncomeWasEntered
            }), true);

        $this->eventBus->expects(self::never())->method('publish');

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
        $accountId = UuidMother::create();
        $dueDate = (new DateTime('+15 days'))->format('Y-m-d');

        $command = new EnterIncomeCommand(
            $incomeId->value(),
            $amount,
            $residentUnitId,
            $typeId,
            $accountId,
            $dueDate,
            null
        );

        $residentUnitMock = $this->createMock(ResidentUnit::class);
        $residentUnitMock->method('id')->willReturn($residentUnitId);
        $incomeTypeMock = $this->createMock(IncomeType::class);

        $this->residentUnitRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($residentUnitId)
            ->willReturn($residentUnitMock);

        $this->incomeTypeRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($typeId)
            ->willReturn($incomeTypeMock);

        $this->accountRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($accountId)
            ->willReturn(AccountMother::create(AccountIdMother::create($accountId)));

        $this->incomeRepository
            ->expects(self::once())
            ->method('save')
            ->with($this->callback(function (Income $income) {
                $events = $income->pullDomainEvents();
                return count($events) === 1;
            }), true);

        $this->eventBus->expects(self::never())->method('publish');

        ($this->handler)($command);
    }

    /** @test */
    public function test_it_propagates_exception_when_resident_unit_not_found(): void
    {
        $incomeId = IncomeIdMother::create();
        $command = new EnterIncomeCommand(
            $incomeId->value(),
            1000,
            'non-existent-unit',
            'type-id',
            UuidMother::create(),
            (new DateTime('+30 days'))->format('Y-m-d'),
            'description'
        );

        $this->residentUnitRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with('non-existent-unit')
            ->willThrowException(new RuntimeException('Resident unit not found'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Resident unit not found');

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
            UuidMother::create(),
            (new DateTime('+30 days'))->format('Y-m-d'),
            'description'
        );

        $residentUnitMock = $this->createMock(ResidentUnit::class);

        $this->residentUnitRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($residentUnitId)
            ->willReturn($residentUnitMock);

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
        $incomeId   = IncomeIdMother::create();
        $accountId  = UuidMother::create();

        $command = new EnterIncomeCommand(
            $incomeId->value(),
            1000,
            'resident-unit-id',
            'type-id',
            $accountId,
            'invalid-date',
            'description'
        );

        $residentUnitMock = $this->createMock(ResidentUnit::class);
        $incomeTypeMock = $this->createMock(IncomeType::class);

        $this->residentUnitRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->willReturn($residentUnitMock);

        $this->incomeTypeRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->willReturn($incomeTypeMock);

        $this->accountRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($accountId)
            ->willReturn(AccountMother::create(AccountIdMother::create($accountId)));

        $this->expectException(DateMalformedStringException::class);

        ($this->handler)($command);
    }

    /** @test
     * @throws Exception
     */
    public function test_it_propagates_due_date_must_be_in_future_exception(): void
    {
        $incomeId   = IncomeIdMother::create();
        $accountId  = UuidMother::create();
        $pastDate = (new DateTime('-1 day'))->format('Y-m-d');

        $command = new EnterIncomeCommand(
            $incomeId->value(),
            1000,
            'resident-unit-id',
            'type-id',
            $accountId,
            $pastDate,
            'description'
        );

        $residentUnitMock = $this->createMock(ResidentUnit::class);
        $incomeTypeMock = $this->createMock(IncomeType::class);

        $this->residentUnitRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->willReturn($residentUnitMock);

        $this->incomeTypeRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->willReturn($incomeTypeMock);

        $this->accountRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($accountId)
            ->willReturn(AccountMother::create(AccountIdMother::create($accountId)));

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
        $accountId = UuidMother::create();
        $dueDate = (new DateTime('+30 days'))->format('Y-m-d');

        $command = new EnterIncomeCommand(
            $incomeId->value(),
            $amount,
            $residentUnitId,
            $typeId,
            $accountId,
            $dueDate,
            null
        );

        $residentUnitMock = $this->createMock(ResidentUnit::class);
        $residentUnitMock->method('id')->willReturn($residentUnitId);
        $incomeTypeMock = $this->createMock(IncomeType::class);

        $this->residentUnitRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->willReturn($residentUnitMock);

        $this->incomeTypeRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->willReturn($incomeTypeMock);

        $this->accountRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($accountId)
            ->willReturn(AccountMother::create(AccountIdMother::create($accountId)));

        // Simulate repository save failure
        $this->incomeRepository
            ->expects(self::once())
            ->method('save')
            ->willThrowException(new RuntimeException('Database connection failed'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database connection failed');

        ($this->handler)($command);
    }
}
