<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Domain\Service;

use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Slip\Domain\Service\MonthlyExpenseAggregatorService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MonthlyExpenseAggregatorServiceTest extends TestCase
{
    private MonthlyExpenseAggregatorService $aggregator;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->aggregator = new MonthlyExpenseAggregatorService($this->logger);
    }

    public function testAggregateTotalsHappyPath(): void
    {
        // Arrange
        $typeEqual = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'EQUAL']);
        $typeFraction = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'FRACTION']);
        $typeIndividual = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'INDIVIDUAL']);

        $expenses = [
            $this->createConfiguredMock(Expense::class, ['amount' => 1000, 'type' => $typeEqual]),
            $this->createConfiguredMock(Expense::class, ['amount' => 2000, 'type' => $typeFraction]),
            $this->createConfiguredMock(Expense::class, ['amount' => 3000, 'type' => $typeIndividual]),
            $this->createConfiguredMock(Expense::class, ['amount' => 1500, 'type' => $typeEqual]), // Another equal expense
        ];

        // Act
        $totals = $this->aggregator->aggregateTotals($expenses);

        // Assert
        $this->assertEquals(2500, $totals['equal']);       // 1000 + 1500
        $this->assertEquals(2000, $totals['fraction']);    // 2000
        $this->assertEquals(3000, $totals['individual']);  // 3000
        $this->assertEquals(7500, $totals['grandTotal']);  // 1000 + 2000 + 3000 + 1500
    }

    public function testAggregateTotalsWithEmptyList(): void
    {
        // Arrange
        $expenses = [];

        // Act
        $totals = $this->aggregator->aggregateTotals($expenses);

        // Assert
        $this->assertEquals(0, $totals['equal']);
        $this->assertEquals(0, $totals['fraction']);
        $this->assertEquals(0, $totals['individual']);
        $this->assertEquals(0, $totals['grandTotal']);
    }

    public function testAggregateTotalsWithOnlyOneType(): void
    {
        // Arrange
        $typeFraction = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'FRACTION']);

        $expenses = [
            $this->createConfiguredMock(Expense::class, ['amount' => 2000, 'type' => $typeFraction]),
            $this->createConfiguredMock(Expense::class, ['amount' => 2500, 'type' => $typeFraction]),
        ];

        // Act
        $totals = $this->aggregator->aggregateTotals($expenses);

        // Assert
        $this->assertEquals(0, $totals['equal']);
        $this->assertEquals(4500, $totals['fraction']);
        $this->assertEquals(0, $totals['individual']);
        $this->assertEquals(4500, $totals['grandTotal']);
    }

    public function testAggregateTotalsWithNullExpenseTypeLogsError(): void
    {
        // Arrange
        $expenses = [
            $this->createConfiguredMock(Expense::class, ['amount' => 5000, 'type' => null, 'description' => 'Faulty Expense'])
        ];

        // Expect logger to be called
        $this->logger->expects($this->once())->method('error');

        // Act
        $totals = $this->aggregator->aggregateTotals($expenses);

        // Assert
        // The faulty expense should not be counted
        $this->assertEquals(0, $totals['grandTotal']);
    }

    public function testAggregateTotalsWithUnknownDistributionMethodFallsBackToIndividual(): void
    {
        // Arrange
        $typeUnknown = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'UNRECOGNIZED']);
        $expenses = [
            $this->createConfiguredMock(Expense::class, ['amount' => 4000, 'type' => $typeUnknown])
        ];

        // Act
        $totals = $this->aggregator->aggregateTotals($expenses);

        // Assert
        $this->assertEquals(0, $totals['equal']);
        $this->assertEquals(0, $totals['fraction']);
        $this->assertEquals(4000, $totals['individual']);
        $this->assertEquals(4000, $totals['grandTotal']);
    }
}
