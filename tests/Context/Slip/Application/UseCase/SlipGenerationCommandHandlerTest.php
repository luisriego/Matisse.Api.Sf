<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Application\UseCase;

use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\Expense;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\Slip\Application\UseCase\SlipGenerationCommand;
use App\Context\Slip\Application\UseCase\SlipGenerationCommandHandler;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Slip\Domain\Service\ExpenseDistributor;
use App\Context\Slip\Domain\Slip;
use App\Context\Slip\Domain\SlipRepository;
use App\Shared\Domain\ValueObject\DateRange;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SlipGenerationCommandHandlerTest extends TestCase
{
    private SlipGenerationCommandHandler $handler;
    private SlipRepository&MockObject $slipRepo;
    private ExpenseRepository&MockObject $expenseRepo;
    private RecurringExpenseRepository&MockObject $recurringRepo;
    private ResidentUnitRepository&MockObject $residentUnitRepo;
    private ExpenseDistributor&MockObject $expenseDistributor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->slipRepo = $this->createMock(SlipRepository::class);
        $this->expenseRepo = $this->createMock(ExpenseRepository::class);
        $this->recurringRepo = $this->createMock(RecurringExpenseRepository::class);
        $this->residentUnitRepo = $this->createMock(ResidentUnitRepository::class);
        $this->expenseDistributor = $this->createMock(ExpenseDistributor::class);

        $this->handler = new SlipGenerationCommandHandler(
            $this->slipRepo,
            $this->expenseRepo,
            $this->recurringRepo,
            $this->residentUnitRepo,
            $this->expenseDistributor
        );
    }

    /**
     * @test
     * @throws \DateMalformedStringException
     */
    public function test_it_generates_and_persists_slips_correctly(): void
    {
        // Arrange
        $year = 2025;
        $month = 7;
        $command = new SlipGenerationCommand($year, $month);
        $range = DateRange::fromMonth($year, $month);

        // Mock expenses
        $expense1 = $this->createMock(Expense::class);
        $expense1->method('amount')->willReturn(10000);
        $expenses = [$expense1];

        $recurringExpense1 = $this->createMock(RecurringExpense::class);
        $recurringExpense1->method('amount')->willReturn(20000);
        $recurring = [$recurringExpense1];

        $allExpenses = array_merge($expenses, $recurring);

        $this->expenseRepo->expects($this->once())
            ->method('findActiveByDateRange')
            ->with($this->equalTo($range)) // Use PHPUnit's built-in matcher
            ->willReturn($expenses);

        $this->recurringRepo->expects($this->once())
            ->method('findActiveForDateRange')
            ->with($this->equalTo($range)) // Use PHPUnit's built-in matcher
            ->willReturn($recurring);

        // Mock residential units
        $unitA = $this->createMock(ResidentUnit::class);
        $unitA->method('id')->willReturn('unit-01');
        $unitB = $this->createMock(ResidentUnit::class);
        $unitB->method('id')->willReturn('unit-02');
        $allUnits = [$unitA, $unitB];

        $this->residentUnitRepo->expects($this->once())
            ->method('findAllActive')
            ->willReturn($allUnits);

        // Mock distribution
        $distribution = [
            'unit-01' => 15000,
            'unit-02' => 15000,
        ];

        $this->expenseDistributor->expects($this->once())
            ->method('distribute')
            ->with($allExpenses, $allUnits)
            ->willReturn($distribution);

        // Mock findOneByIdOrFail which is called inside the loop
        $this->residentUnitRepo->method('findOneByIdOrFail')
            ->willReturnMap([
                ['unit-01', $unitA],
                ['unit-02', $unitB],
            ]);

        // Expect slips to be saved for each unit
        $expectedAssertions = [
            function (Slip $slip) use ($unitA) {
                $this->assertSame($unitA, $slip->residentUnit(), 'First slip should be for unit A');
                $this->assertSame(15000, $slip->amount(), 'Amount for unit A should be correct');
            },
            function (Slip $slip) use ($unitB) {
                $this->assertSame($unitB, $slip->residentUnit(), 'Second slip should be for unit B');
                $this->assertSame(15000, $slip->amount(), 'Amount for unit B should be correct');
            }
        ];

        $this->slipRepo->expects($this->exactly(2))
            ->method('save')
            ->with($this->callback(function (Slip $slip) use (&$expectedAssertions) {
                $assertion = array_shift($expectedAssertions);
                $assertion($slip);
                return true;
            }), $this->equalTo(false));

        $this->slipRepo->expects($this->once())->method('flush');


        // Act
        ($this->handler)($command);
    }
}