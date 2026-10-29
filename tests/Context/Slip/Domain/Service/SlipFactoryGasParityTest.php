<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Domain\Service;

use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\Slip\Domain\Service\GasConsumptionBreakdownResolver;
use App\Context\Slip\Domain\Service\GasExpenseByUnitResolver;
use App\Context\Slip\Domain\Service\MonthlyExpenseAggregatorService;
use App\Context\Slip\Domain\Service\RecurringExpenseSlipContributionService;
use App\Context\Slip\Domain\Service\SlipComponentBreakdownService;
use App\Context\Slip\Domain\Service\SlipFactory;
use App\Context\Slip\Domain\Service\SyndicFeeSlipPoolAdjustmentService;
use DateMalformedStringException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SlipFactoryGasParityTest extends TestCase
{
    private SlipFactory $factory;
    private MockObject|MonthlyExpenseAggregatorService $monthlyExpenseAggregatorService;
    private SlipComponentBreakdownService $slipComponentBreakdownService;
    private GasExpenseByUnitResolver&MockObject $gasExpenseByUnitResolver;
    private GasConsumptionBreakdownResolver&MockObject $gasConsumptionBreakdownResolver;
    private LoggerInterface&MockObject $logger;

    /** @var array<int, Expense> */
    private array $testExpenses = [];
    /** @var ResidentUnit[] */
    private array $testResidentUnits = [];
    /** @var array<string, mixed> */
    private array $testGasBreakdown = [];
    /** @var array<string, int> */
    private array $testFallbackGas = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->monthlyExpenseAggregatorService = $this->createMock(MonthlyExpenseAggregatorService::class);
        $this->slipComponentBreakdownService = new SlipComponentBreakdownService();
        $this->gasExpenseByUnitResolver = $this->createMock(GasExpenseByUnitResolver::class);
        $this->gasConsumptionBreakdownResolver = $this->createMock(GasConsumptionBreakdownResolver::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Use callbacks that read from test-controlled state
        $this->gasConsumptionBreakdownResolver->method('breakdownForMonth')->willReturnCallback(
            fn () => $this->testGasBreakdown,
        );
        $this->gasExpenseByUnitResolver->method('sumByResidentUnitForCalendarMonth')->willReturnCallback(
            fn () => $this->testFallbackGas,
        );

        $this->factory = new SlipFactory(
            $this->monthlyExpenseAggregatorService,
            new RecurringExpenseSlipContributionService(),
            new SyndicFeeSlipPoolAdjustmentService(
                new RecurringExpenseSlipContributionService(),
                $this->monthlyExpenseAggregatorService,
            ),
            $this->slipComponentBreakdownService,
            $this->gasExpenseByUnitResolver,
            $this->logger,
            $this->gasConsumptionBreakdownResolver,
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testSlipFactoryUsesGasConsumptionBreakdownWhenReadingsExist(): void
    {
        // Gas readings for unit-1: 10 m3 * 2500 cents/m3 = 25000 cents
        $this->testGasBreakdown = [
            'pricePerM3Cents' => 2500,
            'byUnit' => [
                'unit-1' => [
                    'previousReading' => 100.0,
                    'previousMonth' => '2024-04',
                    'currentReading' => 110.0,
                    'currentMonth' => '2024-05',
                    'consumptionM3' => 10.0,
                    'gasCents' => 25000,
                ],
            ],
            'gasTotalCents' => 25000,
        ];
        $this->testFallbackGas = [];

        $typeEqual = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'EQUAL']);
        $this->testExpenses = [$this->createConfiguredMock(Expense::class, ['amount' => 0, 'type' => $typeEqual])];

        $resident1 = $this->createConfiguredMock(ResidentUnit::class, [
            'id' => 'unit-1',
            'idealFraction' => 0.5,
            'unit' => '101',
        ]);
        $resident2 = $this->createConfiguredMock(ResidentUnit::class, [
            'id' => 'unit-2',
            'idealFraction' => 0.5,
            'unit' => '201',
        ]);
        $this->testResidentUnits = [$resident1, $resident2];

        $this->monthlyExpenseAggregatorService->method('aggregateTotals')->willReturn([
            'equal' => 0,
            'fraction' => 0,
            'individual' => 0,
            'individualByUnit' => [],
        ]);

        // Act
        $slips = $this->factory->createFromExpensesAndUnits(
            $this->testExpenses,
            [],
            $this->testResidentUnits,
            2024,
            5,
        );

        // Assert: unit-1 gets gasCents = 25000; unit-2 has 0 totalCents so it's skipped
        $this->assertCount(1, $slips);

        $amounts = [];

        foreach ($slips as $slip) {
            $amounts[$slip->residentUnit()->id()] = $slip->amount();
        }

        // unit-1 gets 25000 cents from gas (equal pool is 0, fraction is 0, individual is 0)
        $this->assertEquals(25000, $amounts['unit-1']);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testSlipFactoryFallsBackToGasExpenseByUnitResolverWhenReadingsTotalZero(): void
    {
        // GasConsumptionBreakdownResolver returns gasTotalCents=0 (no readings)
        $this->testGasBreakdown = [
            'pricePerM3Cents' => 0,
            'byUnit' => [
                'unit-1' => [
                    'previousReading' => null,
                    'previousMonth' => '2024-04',
                    'currentReading' => null,
                    'currentMonth' => '2024-05',
                    'consumptionM3' => 0.0,
                    'gasCents' => 0,
                ],
            ],
            'gasTotalCents' => 0,
        ];

        // Fallback: GasExpenseByUnitResolver returns gas expense for unit-1
        $this->testFallbackGas = [
            'unit-1' => 15000,
        ];

        $typeEqual = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'EQUAL']);
        $this->testExpenses = [$this->createConfiguredMock(Expense::class, ['amount' => 0, 'type' => $typeEqual])];

        $resident1 = $this->createConfiguredMock(ResidentUnit::class, [
            'id' => 'unit-1',
            'idealFraction' => 0.5,
            'unit' => '101',
        ]);
        $resident2 = $this->createConfiguredMock(ResidentUnit::class, [
            'id' => 'unit-2',
            'idealFraction' => 0.5,
            'unit' => '201',
        ]);
        $this->testResidentUnits = [$resident1, $resident2];

        $this->monthlyExpenseAggregatorService->method('aggregateTotals')->willReturn([
            'equal' => 0,
            'fraction' => 0,
            'individual' => 0,
            'individualByUnit' => [],
        ]);

        // Act
        $slips = $this->factory->createFromExpensesAndUnits(
            $this->testExpenses,
            [],
            $this->testResidentUnits,
            2024,
            5,
        );

        // Assert: unit-1 gets gasCents = 15000 from fallback resolver; unit-2 skipped
        $this->assertCount(1, $slips);

        $amounts = [];

        foreach ($slips as $slip) {
            $amounts[$slip->residentUnit()->id()] = $slip->amount();
        }

        // unit-1 gets 15000 cents from fallback gas expense
        $this->assertEquals(15000, $amounts['unit-1']);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testSlipFactoryWithGasPriceOverride(): void
    {
        // Gas readings with override price: 10 m3 * 3000 cents/m3 = 30000 cents
        $this->testGasBreakdown = [
            'pricePerM3Cents' => 3000,
            'byUnit' => [
                'unit-1' => [
                    'previousReading' => 100.0,
                    'previousMonth' => '2024-04',
                    'currentReading' => 110.0,
                    'currentMonth' => '2024-05',
                    'consumptionM3' => 10.0,
                    'gasCents' => 30000,
                ],
            ],
            'gasTotalCents' => 30000,
        ];
        $this->testFallbackGas = [];

        $typeEqual = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'EQUAL']);
        $this->testExpenses = [$this->createConfiguredMock(Expense::class, ['amount' => 0, 'type' => $typeEqual])];

        $resident1 = $this->createConfiguredMock(ResidentUnit::class, [
            'id' => 'unit-1',
            'idealFraction' => 0.5,
            'unit' => '101',
        ]);
        $resident2 = $this->createConfiguredMock(ResidentUnit::class, [
            'id' => 'unit-2',
            'idealFraction' => 0.5,
            'unit' => '201',
        ]);
        $this->testResidentUnits = [$resident1, $resident2];

        $this->monthlyExpenseAggregatorService->method('aggregateTotals')->willReturn([
            'equal' => 0,
            'fraction' => 0,
            'individual' => 0,
            'individualByUnit' => [],
        ]);

        // Act: pass gasPricePerM3CentsOverride
        $slips = $this->factory->createFromExpensesAndUnits(
            $this->testExpenses,
            [],
            $this->testResidentUnits,
            2024,
            5,
            gasPricePerM3CentsOverride: 3000,
        );

        // Assert: unit-1 gets gasCents = 30000 (using override price)
        $this->assertCount(1, $slips);
        $this->assertEquals(30000, $slips[0]->amount());
    }
}
