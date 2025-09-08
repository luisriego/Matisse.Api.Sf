<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Application\UseCase\SlipGeneration;

use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\Slip\Application\UseCase\SlipGeneration\SlipGenerationCommand;
use App\Context\Slip\Application\UseCase\SlipGeneration\SlipGenerationCommandHandler;
use App\Context\Slip\Domain\Service\MonthlyExpenseAggregatorService;
use App\Context\Slip\Domain\Service\SlipAmountCalculatorService;
use App\Context\Slip\Domain\Service\SlipFactory;
use App\Context\Slip\Domain\Service\SlipGenerationPolicy;
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
    private SlipGenerationCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->slipRepository = $this->createMock(SlipRepository::class);
        $this->expenseRepository = $this->createMock(ExpenseRepository::class);
        $this->recurringExpenseRepository = $this->createMock(RecurringExpenseRepository::class);
        $this->residentUnitRepository = $this->createMock(ResidentUnitRepository::class);
        $this->generationPolicy = $this->createMock(SlipGenerationPolicy::class);
        $logger = $this->createMock(LoggerInterface::class);

        $slipFactory = new SlipFactory(
            new MonthlyExpenseAggregatorService($logger),
            new SlipAmountCalculatorService($logger),
            $logger
        );

        $this->handler = new SlipGenerationCommandHandler(
            $this->slipRepository,
            $this->expenseRepository,
            $this->recurringExpenseRepository,
            $this->residentUnitRepository,
            $this->generationPolicy,
            $slipFactory
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

        $resident = $this->createConfiguredMock(ResidentUnit::class, ['id' => 'resident-1', 'idealFraction' => 0.0]);
        $this->residentUnitRepository->method('findAllActive')->willReturn([$resident]);

        $this->slipRepository->expects($this->once())->method('save');
        $this->slipRepository->expects($this->once())->method('flush');

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

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        ($this->handler)($command);
    }

    public function testInvokeWithNoExpensesDoesNotSaveSlips(): void
    {
        // Arrange
        $command = new SlipGenerationCommand(2024, 5);

        $this->expenseRepository->method('findActiveByDateRange')->willReturn([]);
        $this->recurringExpenseRepository->method('findActiveForDateRange')->willReturn([]);
        $this->residentUnitRepository->method('findAllActive')->willReturn([$this->createMock(ResidentUnit::class)]);

        $this->slipRepository->expects($this->never())->method('save');
        $this->slipRepository->expects($this->never())->method('flush');

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
        $this->residentUnitRepository->method('findAllActive')->willReturn([]);

        $this->slipRepository->expects($this->never())->method('save');
        $this->slipRepository->expects($this->never())->method('flush');

        // Act
        ($this->handler)($command);
    }
}
