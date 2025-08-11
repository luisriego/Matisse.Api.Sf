<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\UpdateRecurringExpense;

use App\Context\Expense\Application\UseCase\UpdateRecurringExpense\UpdateRecurrentExpenseCommand;
use App\Context\Expense\Application\UseCase\UpdateRecurringExpense\UpdateRecurrentExpenseCommandHandler;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeId;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeRepository;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Tests\Context\Expense\Domain\ExpenseIdMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use DateTime;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;


class UpdateRecurringExpenseCommandHandlerTest extends TestCase
{
    private RecurringExpenseRepository&MockObject $recurringExpenseRepo;
    private ExpenseTypeRepository&MockObject $typeRepo;
    private UpdateRecurrentExpenseCommandHandler $handler;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->recurringExpenseRepo = $this->createMock(RecurringExpenseRepository::class);
        $this->typeRepo = $this->createMock(ExpenseTypeRepository::class);
        $this->handler = new UpdateRecurrentExpenseCommandHandler(
            $this->recurringExpenseRepo,
            $this->typeRepo
        );
    }

    /** @test
     * @throws Exception
     * @throws \DateMalformedStringException
     */
    public function test_it_updates_all_fields(): void
    {
        $idMother    = ExpenseIdMother::create();
        $typeMother  = ExpenseTypeMother::create();
        $months      = [1, 6, 12];
        $startString = '2025-01-15';
        $endString   = '2025-12-15';
        $description = 'desc';
        $notes       = 'notes';

        $command = new UpdateRecurrentExpenseCommand(
            $idMother->value(),
            500,
            $typeMother->id(),
            10,
            $months,
            $startString,
            $endString,
            $description,
            $notes
        );

        $expenseMock = $this->createMock(RecurringExpense::class);

        // find by id
        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($idMother->value())
            ->willReturn($expenseMock);

        // type lookup
        $this->typeRepo
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($typeMother->id())
            ->willReturn($typeMother);

        // expect each updateXXX
        $expenseMock->expects(self::once())->method('updateAmount')->with(500);
        $expenseMock->expects(self::once())->method('updateType')->with($typeMother);
        $expenseMock->expects(self::once())->method('updateDueDay')->with(10);
        $expenseMock->expects(self::once())->method('updateMonthsOfYear')->with($months);
        $expenseMock->expects(self::once())
            ->method('updateStartDate')
            ->with(self::callback(fn($dt) => $dt instanceof DateTime && $dt->format('Y-m-d') === $startString));
        $expenseMock->expects(self::once())
            ->method('updateEndDate')
            ->with(self::callback(fn($dt) => $dt instanceof DateTime && $dt->format('Y-m-d') === $endString));
        $expenseMock->expects(self::once())->method('updateDescription')->with($description);
        $expenseMock->expects(self::once())->method('updateNotes')->with($notes);

        // finally save
        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('save')
            ->with($expenseMock, true);

        // act
        ($this->handler)($command);
    }

    /** @test
     * @throws \DateMalformedStringException
     * @throws Exception
     */
    public function test_it_updates_only_non_null_fields(): void
    {
        $idMother = ExpenseIdMother::create();
        $command  = new UpdateRecurrentExpenseCommand(
            $idMother->value(),
            123,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );

        $expenseMock = $this->createMock(RecurringExpense::class);

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($idMother->value())
            ->willReturn($expenseMock);

        // no type lookup
        $this->typeRepo
            ->expects(self::never())
            ->method('findOneByIdOrFail');

        // only amount should be updated
        $expenseMock->expects(self::once())->method('updateAmount')->with(123);

        // none of the others
        $expenseMock->expects(self::never())->method('updateType');
        $expenseMock->expects(self::never())->method('updateDueDay');
        $expenseMock->expects(self::never())->method('updateMonthsOfYear');
        $expenseMock->expects(self::never())->method('updateStartDate');
        $expenseMock->expects(self::never())->method('updateEndDate');
        $expenseMock->expects(self::never())->method('updateDescription');
        $expenseMock->expects(self::never())->method('updateNotes');

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('save')
            ->with($expenseMock, true);

        ($this->handler)($command);
    }

    /** @test
     * @throws \DateMalformedStringException
     */
    public function test_it_propagates_exception_when_recurring_not_found(): void
    {
        $idValue = ExpenseIdMother::create()->value();
        $command = new UpdateRecurrentExpenseCommand(
            $idValue, null, null, null, null, null, null, null, null
        );

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($idValue)
            ->willThrowException(new RuntimeException('Not found'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not found');

        ($this->handler)($command);
    }

    /** @test
     * @throws \DateMalformedStringException
     * @throws Exception
     */
    public function test_it_propagates_exception_when_type_not_found(): void
    {
        $idMother  = ExpenseIdMother::create();
        $badTypeId = 'non-existent-type';
        $command   = new UpdateRecurrentExpenseCommand(
            $idMother->value(),
            null,
            $badTypeId,
            null,
            null,
            null,
            null,
            null,
            null
        );

        $expenseMock = $this->createMock(RecurringExpense::class);
        $this->recurringExpenseRepo
            ->method('findOneByIdOrFail')
            ->willReturn($expenseMock);

        // Now expect the InvalidArgumentException from ExpenseTypeId
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            '<%s> does not allow the value <%s>.',
            ExpenseTypeId::class,
            $badTypeId
        ));

        ($this->handler)($command);
    }
}