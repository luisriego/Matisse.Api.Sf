<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Application\UseCase\SlipGeneration;

use App\Context\BillingPolicy\Domain\BillingPolicyResolverPort;
use App\Context\BillingPolicy\Domain\ResolvedBillingPolicy;
use App\Context\Slip\Domain\Service\GasExpenseByUnitResolver;
use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\Slip\Application\UseCase\SlipGeneration\SlipGenerationCommand;
use App\Context\Slip\Application\UseCase\SlipGeneration\SlipGenerationCommandHandler;
use App\Context\Slip\Domain\Service\MonthlyExpenseAggregatorService;
use App\Context\Slip\Domain\Service\RecurringExpenseSlipContributionService;
use App\Context\Slip\Domain\Service\SlipComponentBreakdownService;
use App\Context\Slip\Domain\Service\SlipFactory;
use App\Context\Slip\Domain\PeriodClosureRepository;
use App\Context\Slip\Domain\Service\PeriodClosureGuard;
use App\Context\Slip\Domain\Service\SlipGenerationPolicy;
use App\Context\Slip\Domain\Service\SyndicFeeSlipPoolAdjustmentService;
use App\Context\Slip\Domain\SlipGenerationParameterSnapshotRepository;
use App\Context\Slip\Domain\SlipRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SlipGenerationCommandHandlerTest extends TestCase
{
    private MockObject|SlipRepository $slipRepository;
    private MockObject|ExpenseRepository $expenseRepository;
    private MockObject|RecurringExpenseRepository $recurringExpenseRepository;
    private MockObject|ResidentUnitRepository $residentUnitRepository;
    private MockObject|SlipGenerationPolicy $generationPolicy;
    private MockObject|MonthlyExpenseAggregatorService $monthlyExpenseAggregatorService;
    private SlipComponentBreakdownService $slipComponentBreakdownService;
    private MockObject|GasExpenseByUnitResolver $gasExpenseByUnitResolver;
    private MockObject|LoggerInterface $logger;
    private MockObject|SlipGenerationParameterSnapshotRepository $snapshotRepository;
    private MockObject|PeriodClosureRepository $periodClosureRepository;
    private MockObject|BillingPolicyResolverPort $billingPolicyResolverService;

    private SlipFactory $slipFactory;
    private SlipGenerationCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->slipRepository = $this->createMock(SlipRepository::class);
        $this->expenseRepository = $this->createMock(ExpenseRepository::class);
        $this->recurringExpenseRepository = $this->createMock(RecurringExpenseRepository::class);
        $this->residentUnitRepository = $this->createMock(ResidentUnitRepository::class);
        $this->generationPolicy = $this->createMock(SlipGenerationPolicy::class);

        // Inicializa los mocks para las dependencias de SlipFactory
        $this->monthlyExpenseAggregatorService = $this->createMock(MonthlyExpenseAggregatorService::class);
        $this->slipComponentBreakdownService = new SlipComponentBreakdownService();
        $this->gasExpenseByUnitResolver = $this->createMock(GasExpenseByUnitResolver::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->snapshotRepository = $this->createMock(SlipGenerationParameterSnapshotRepository::class);
        $this->periodClosureRepository = $this->createMock(PeriodClosureRepository::class);
        $this->periodClosureRepository->method('existsForMonth')->willReturn(false);
        $this->billingPolicyResolverService = $this->createMock(BillingPolicyResolverPort::class);
        $this->billingPolicyResolverService
            ->method('resolve')
            ->willReturnCallback(static fn (string $targetMonth) => ResolvedBillingPolicy::empty($targetMonth));

        $this->gasExpenseByUnitResolver->method('sumByResidentUnitForCalendarMonth')->willReturn([]);

        $recurringService = new RecurringExpenseSlipContributionService();

        // Instancia SlipFactory con los mocks correctos
        $this->slipFactory = new SlipFactory(
            $this->monthlyExpenseAggregatorService,
            $recurringService,
            new SyndicFeeSlipPoolAdjustmentService($recurringService, $this->monthlyExpenseAggregatorService),
            $this->slipComponentBreakdownService,
            $this->gasExpenseByUnitResolver,
            $this->logger,
        );

        // Instancia el handler pasando la SlipFactory correctamente configurada
        $this->handler = new SlipGenerationCommandHandler(
            $this->slipRepository,
            $this->expenseRepository,
            $this->recurringExpenseRepository,
            $this->residentUnitRepository,
            $this->generationPolicy,
            $this->slipFactory,
            $this->snapshotRepository,
            new PeriodClosureGuard($this->periodClosureRepository),
            $this->billingPolicyResolverService,
        );
    }

    public function testInvokeHappyPath(): void
    {
        // Arrange
        $command = new SlipGenerationCommand(2024, 5);

        $this->generationPolicy->expects($this->once())->method('check');
        $this->slipRepository->expects($this->once())->method('deleteByDateRange');

        $typeEqual = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'EQUAL']);
        $expenses = [$this->createConfiguredMock(Expense::class, ['amount' => 10000, 'type' => $typeEqual])];
        $this->expenseRepository->method('findActiveByDateRange')->willReturn($expenses);
        $this->recurringExpenseRepository->method('findActiveForDateRange')->willReturn([]);

        $resident = $this->createConfiguredMock(ResidentUnit::class, ['id' => 'resident-1', 'idealFraction' => 0.0]);
        $this->residentUnitRepository->method('findAllActive')->willReturn([$resident]);

        // Configura el mock de monthlyExpenseAggregatorService para que devuelva los totales esperados
        $this->monthlyExpenseAggregatorService->method('aggregateTotals')->willReturn([
            'equal' => 10000,
            'fraction' => 0,
            'individual' => 0,
            'individualByUnit' => [],
        ]);

        $this->slipRepository->expects($this->once())->method('save');
        $this->slipRepository->expects($this->once())->method('flush');
        $this->snapshotRepository->expects($this->once())->method('upsertForExpenseMonth')->with(2024, 5, 0, 0);

        // Act
        ($this->handler)($command);
    }

    public function testInvokeStopsWhenPolicyFails(): void
    {
        // Arrange
        $command = new SlipGenerationCommand(2024, 1);

        $this->generationPolicy->expects($this->once())
            ->method('check')
            ->willThrowException(new \RuntimeException('Generation not allowed.'));

        $this->slipRepository->expects($this->never())->method('deleteByDateRange');
        $this->slipRepository->expects($this->never())->method('save');
        $this->slipRepository->expects($this->never())->method('flush');
        $this->snapshotRepository->expects($this->never())->method('upsertForExpenseMonth');

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        ($this->handler)($command);
    }

    public function testInvokeWithNoExpensesDoesNotSaveSlips(): void
    {
        // Arrange
        $command = new SlipGenerationCommand(2024, 5);

        $this->generationPolicy->expects($this->once())->method('check');
        $this->slipRepository->expects($this->once())->method('deleteByDateRange');

        $this->expenseRepository->method('findActiveByDateRange')->willReturn([]);
        $this->recurringExpenseRepository->method('findActiveForDateRange')->willReturn([]);
        $this->residentUnitRepository->method('findAllActive')->willReturn([
            $this->createConfiguredMock(ResidentUnit::class, ['id' => 'resident-1', 'unit' => '101', 'idealFraction' => 0.0]),
        ]);

        // Configura el mock de monthlyExpenseAggregatorService para que devuelva cero
        $this->monthlyExpenseAggregatorService->method('aggregateTotals')->willReturn([
            'equal' => 0,
            'fraction' => 0,
            'individual' => 0,
            'individualByUnit' => [],
        ]);

        $this->slipRepository->expects($this->never())->method('save');
        $this->slipRepository->expects($this->once())->method('flush');
        $this->snapshotRepository->expects($this->once())->method('upsertForExpenseMonth')->with(2024, 5, 0, 0);

        // Act
        ($this->handler)($command);
    }

    public function testInvokeWithNoResidentsDoesNotSaveSlips(): void
    {
        // Arrange
        $command = new SlipGenerationCommand(2024, 5);

        $typeEqual = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'EQUAL']);
        $expenses = [$this->createConfiguredMock(Expense::class, ['amount' => 10000, 'type' => $typeEqual])];
        $this->expenseRepository->method('findActiveByDateRange')->willReturn($expenses);
        $this->recurringExpenseRepository->method('findActiveForDateRange')->willReturn([]);
        $this->residentUnitRepository->method('findAllActive')->willReturn([]);

        // Configura el mock de monthlyExpenseAggregatorService para que devuelva los totales esperados
        $this->monthlyExpenseAggregatorService->method('aggregateTotals')->willReturn([
            'equal' => 10000,
            'fraction' => 0,
            'individual' => 0,
            'individualByUnit' => [],
        ]);

        $this->generationPolicy->expects($this->once())->method('check');
        $this->slipRepository->expects($this->once())->method('deleteByDateRange');

        $this->slipRepository->expects($this->never())->method('save');
        $this->slipRepository->expects($this->once())->method('flush');
        $this->snapshotRepository->expects($this->once())->method('upsertForExpenseMonth')->with(2024, 5, 0, 0);

        // Act
        ($this->handler)($command);
    }
}