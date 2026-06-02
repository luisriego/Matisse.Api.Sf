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
        $typeEqual = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'EQUAL', 'code' => 'OTX1']);
        $typeFraction = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'EQUAL', 'code' => 'SP2AG']); // COPASA
        $typeIndividual = $this->createConfiguredMock(ExpenseType::class, [
            'distributionMethod' => 'INDIVIDUAL',
            'code' => 'OTX',
        ]);

        $expenses = [
            $this->createConfiguredMock(Expense::class, ['amount' => 1000, 'type' => $typeEqual, 'id' => 'e1']),
            $this->createConfiguredMock(Expense::class, ['amount' => 2000, 'type' => $typeFraction, 'id' => 'e2']),
            $this->createConfiguredMock(Expense::class, [
                'amount' => 3000,
                'type' => $typeIndividual,
                'id' => 'e3',
                'residentUnitId' => 'unit-a',
            ]),
            $this->createConfiguredMock(Expense::class, ['amount' => 1500, 'type' => $typeEqual, 'id' => 'e4']), // Another equal expense
        ];

        // Act
        $totals = $this->aggregator->aggregateTotals($expenses);

        // Assert
        $this->assertEquals(5500, $totals['equal']);       // 1000 + 3000 + 1500
        $this->assertEquals(2000, $totals['fraction']);    // 2000
        $this->assertEquals(0, $totals['individual']);
        $this->assertEquals([], $totals['individualByUnit']);
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
        $this->assertEquals([], $totals['individualByUnit']);
        $this->assertEquals(0, $totals['grandTotal']);
    }

    public function testAggregateTotalsWithOnlyOneType(): void
    {
        // Arrange
        $typeFraction = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'FRACTION', 'code' => 'SP2AG']);

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
        $this->assertEquals([], $totals['individualByUnit']);
        $this->assertEquals(4500, $totals['grandTotal']);
    }

    public function testAggregateTotalsWithNullExpenseTypeLogsError(): void
    {
        // Arrange
        $expenses = [
            $this->createConfiguredMock(Expense::class, ['amount' => 5000, 'type' => null, 'description' => 'Faulty Expense']),
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
        $typeUnknown = $this->createConfiguredMock(ExpenseType::class, [
            'distributionMethod' => 'UNRECOGNIZED',
            'code' => 'OTX9',
        ]);
        $expenses = [
            $this->createConfiguredMock(Expense::class, ['amount' => 4000, 'type' => $typeUnknown]),
        ];

        // Act
        $totals = $this->aggregator->aggregateTotals($expenses);

        // Assert
        $this->assertEquals(4000, $totals['equal']);
        $this->assertEquals(0, $totals['fraction']);
        $this->assertEquals(0, $totals['individual']);
        $this->assertEquals([], $totals['individualByUnit']);
        $this->assertEquals(4000, $totals['grandTotal']);
    }

    public function testGasExpenseTypeCountsOnlyInGrandTotal(): void
    {
        $typeGas = $this->createConfiguredMock(ExpenseType::class, [
            'distributionMethod' => 'EQUAL',
            'code' => 'SP3GA',
        ]);
        $expenses = [
            $this->createConfiguredMock(Expense::class, [
                'amount' => 999,
                'type' => $typeGas,
                'id' => 'gas-1',
                'residentUnitId' => 'unit-1',
            ]),
        ];

        $totals = $this->aggregator->aggregateTotals($expenses);

        $this->assertEquals(0, $totals['equal']);
        $this->assertEquals(0, $totals['fraction']);
        $this->assertEquals(0, $totals['individual']);
        $this->assertEquals([], $totals['individualByUnit']);
        $this->assertEquals(999, $totals['grandTotal']);
    }
}
