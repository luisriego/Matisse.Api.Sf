<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Application\UseCase;

use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\Expense;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\Slip\Application\UseCase\SlipGenerationCommand;
use App\Context\Slip\Application\UseCase\SlipGenerationCommandHandler;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Slip\Domain\Service\SlipFactory;
use App\Context\Slip\Domain\Service\SlipGenerationPolicy;
use App\Context\Slip\Domain\Slip;
use App\Context\Slip\Domain\SlipRepository;
use App\Shared\Domain\ValueObject\DateRange;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SlipGenerationCommandHandlerTest extends TestCase
{
    private SlipGenerationCommandHandler $handler;
    private SlipRepository&MockObject $slipRepo;
    private ExpenseRepository&MockObject $expenseRepo;
    private RecurringExpenseRepository&MockObject $recurringRepo;
    private ResidentUnitRepository&MockObject $residentUnitRepo;
    private MockObject|SlipGenerationPolicy $generationPolicy;
    private SlipFactory&MockObject $slipFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->slipRepo = $this->createMock(SlipRepository::class);
        $this->expenseRepo = $this->createMock(ExpenseRepository::class);
        $this->recurringRepo = $this->createMock(RecurringExpenseRepository::class);
        $this->residentUnitRepo = $this->createMock(ResidentUnitRepository::class);
        $this->generationPolicy = $this->createMock(SlipGenerationPolicy::class);
        $this->slipFactory = $this->createMock(SlipFactory::class);

        $this->handler = new SlipGenerationCommandHandler(
            $this->slipRepo,
            $this->expenseRepo,
            $this->recurringRepo,
            $this->residentUnitRepo,
            $this->generationPolicy,
            $this->slipFactory
        );
    }

    #[Test]
    public function it_generates_and_persists_slips_correctly(): void
    {
        // Arrange
        $year = 2025;
        $month = 7;
        $command = new SlipGenerationCommand($year, $month);
        $expenseRange = DateRange::fromMonth($year, $month);
        $dueDateRange = DateRange::fromMonth($year, $month + 1);

        // --- Expect the policy to be checked ---
        $this->generationPolicy->expects($this->once())
            ->method('check')
            ->with($year, $month, false);

        // --- Expect old slips to be deleted ---
        $this->slipRepo->expects($this->once())
            ->method('deleteByDateRange')
            ->with($this->equalTo($dueDateRange));

        // --- Data fetching expectations ---
        $expense1 = $this->createMock(Expense::class);
        $expenses = [$expense1];

        $recurringExpense1 = $this->createMock(RecurringExpense::class);
        $recurring = [$recurringExpense1];

        $allExpenses = array_merge($expenses, $recurring);

        $this->expenseRepo->expects($this->once())
            ->method('findActiveByDateRange')
            ->with($this->equalTo($expenseRange))
            ->willReturn($expenses);

        $this->recurringRepo->expects($this->once())
            ->method('findActiveForDateRange')
            ->with($this->equalTo($expenseRange))
            ->willReturn($recurring);

        $unitA = $this->createMock(ResidentUnit::class);
        $unitB = $this->createMock(ResidentUnit::class);
        $allUnits = [$unitA, $unitB];

        $this->residentUnitRepo->expects($this->once())
            ->method('findAllActive')
            ->willReturn($allUnits);

        // --- Factory expectation ---
        $slip1 = $this->createMock(Slip::class);
        $slip2 = $this->createMock(Slip::class);
        $generatedSlips = [$slip1, $slip2];

        $this->slipFactory->expects($this->once())
            ->method('createFromExpensesAndUnits')
            ->with($allExpenses, $allUnits, $year, $month)
            ->willReturn($generatedSlips);

        // --- Persistence expectations ---
        $this->slipRepo->expects($this->exactly(2))
            ->method('save')
            ->with(
                $this->callback(function (Slip $slip) use (&$generatedSlips) {
                    $expectedSlip = array_shift($generatedSlips);
                    $this->assertSame($expectedSlip, $slip, 'The correct slip object should be saved in order.');
                    return true;
                }),
                $this->equalTo(false)
            );

        $this->slipRepo->expects($this->once())->method('flush');


        // Act
        ($this->handler)($command);
    }
}