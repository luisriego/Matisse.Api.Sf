<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Application\UseCase;

use App\Context\BillingPolicy\Domain\BillingPolicyResolverPort;
use App\Context\BillingPolicy\Domain\ResolvedBillingPolicy;
use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\Slip\Application\UseCase\SlipGeneration\SlipGenerationCommand;
use App\Context\Slip\Application\UseCase\SlipGeneration\SlipGenerationCommandHandler;
use App\Context\Slip\Domain\PeriodClosureRepository;
use App\Context\Slip\Domain\Service\PeriodClosureGuard;
use App\Context\Slip\Domain\Service\SlipFactory;
use App\Context\Slip\Domain\Service\SlipGenerationPolicy;
use App\Context\Slip\Domain\Slip;
use App\Context\Slip\Domain\SlipGenerationParameterSnapshotRepository;
use App\Context\Slip\Domain\SlipRepository;
use App\Shared\Domain\ValueObject\DateRange;
use DateMalformedStringException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function array_shift;

final class SlipGenerationCommandHandlerTest extends TestCase
{
    private SlipGenerationCommandHandler $handler;
    private MockObject&SlipRepository $slipRepo;
    private ExpenseRepository&MockObject $expenseRepo;
    private MockObject&RecurringExpenseRepository $recurringRepo;
    private MockObject&ResidentUnitRepository $residentUnitRepo;
    private MockObject|SlipGenerationPolicy $generationPolicy;
    private MockObject&SlipFactory $slipFactory;
    private MockObject&SlipGenerationParameterSnapshotRepository $snapshotRepo;
    private BillingPolicyResolverPort&MockObject $billingPolicyResolverService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->slipRepo = $this->createMock(SlipRepository::class);
        $this->expenseRepo = $this->createMock(ExpenseRepository::class);
        $this->recurringRepo = $this->createMock(RecurringExpenseRepository::class);
        $this->residentUnitRepo = $this->createMock(ResidentUnitRepository::class);
        $this->generationPolicy = $this->createMock(SlipGenerationPolicy::class);
        $this->slipFactory = $this->createMock(SlipFactory::class);
        $this->snapshotRepo = $this->createMock(SlipGenerationParameterSnapshotRepository::class);
        $this->billingPolicyResolverService = $this->createMock(BillingPolicyResolverPort::class);
        $this->billingPolicyResolverService
            ->method('resolve')
            ->willReturn(ResolvedBillingPolicy::empty('2099-07'));

        $periodClosureRepo = $this->createMock(PeriodClosureRepository::class);
        $periodClosureRepo->method('existsForMonth')->willReturn(false);

        $this->handler = new SlipGenerationCommandHandler(
            $this->slipRepo,
            $this->expenseRepo,
            $this->recurringRepo,
            $this->residentUnitRepo,
            $this->generationPolicy,
            $this->slipFactory,
            $this->snapshotRepo,
            new PeriodClosureGuard($periodClosureRepo),
            $this->billingPolicyResolverService,
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    #[Test]
    public function itGeneratesAndPersistsSlipsCorrectly(): void
    {
        // Arrange
        $year = 2099;
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
            ->with($expenses, $recurring, $allUnits, $year, $month, 0, 0, null)
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
                $this->equalTo(false),
            );

        $this->slipRepo->expects($this->once())->method('flush');

        $this->snapshotRepo->expects($this->once())->method('upsertForExpenseMonth')->with($year, $month, 0, 0);

        // Act
        ($this->handler)($command);
    }
}
