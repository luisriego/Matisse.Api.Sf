<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Domain\Service;

use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Slip\Domain\Service\GasExpenseByUnitResolver;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\Slip\Domain\Service\MonthlyExpenseAggregatorService;
use App\Context\Slip\Domain\Service\RecurringExpenseSlipContributionService;
use App\Context\Slip\Domain\Service\SlipComponentBreakdownService;
use App\Context\Slip\Domain\Service\SlipFactory;
use App\Context\Slip\Domain\Slip;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject; // Asegúrate de que esta línea exista
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SlipFactoryTest extends TestCase
{
    private SlipFactory $factory;
    private MockObject|MonthlyExpenseAggregatorService $monthlyExpenseAggregatorService; // Añadido
    private SlipComponentBreakdownService $slipComponentBreakdownService;
    private MockObject|GasExpenseByUnitResolver $gasExpenseByUnitResolver;
    private MockObject|LoggerInterface $logger; // Añadido

    protected function setUp(): void
    {
        parent::setUp();

        // Inicializa los mocks para las dependencias de SlipFactory
        $this->monthlyExpenseAggregatorService = $this->createMock(MonthlyExpenseAggregatorService::class);
        $this->slipComponentBreakdownService = new SlipComponentBreakdownService();
        $this->gasExpenseByUnitResolver = $this->createMock(GasExpenseByUnitResolver::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->gasExpenseByUnitResolver->method('sumByResidentUnitForCalendarMonth')->willReturn([]);

        // Instancia SlipFactory con los mocks correctos
        $this->factory = new SlipFactory(
            $this->monthlyExpenseAggregatorService,
            new RecurringExpenseSlipContributionService(),
            $this->slipComponentBreakdownService,
            $this->gasExpenseByUnitResolver,
            $this->logger,
        );
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function testCreateFromExpensesAndUnitsHappyPath(): void
    {
        // Arrange
        $typeEqual = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'EQUAL']);
        $typeFraction = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'FRACTION']);
        $allExpenses = [
            $this->createConfiguredMock(Expense::class, ['amount' => 10000, 'type' => $typeEqual]),
            $this->createConfiguredMock(Expense::class, ['amount' => 50000, 'type' => $typeFraction]),
        ];

        $resident1 = $this->createConfiguredMock(ResidentUnit::class, ['id' => 'resident-1', 'idealFraction' => 0.45, 'unit' => '101']);
        $resident2 = $this->createConfiguredMock(ResidentUnit::class, ['id' => 'resident-2', 'idealFraction' => 0.55, 'unit' => '201']);
        $residentUnits = [$resident1, $resident2];

        // Configura los mocks de los servicios internos de SlipFactory
        $this->monthlyExpenseAggregatorService->method('aggregateTotals')->willReturn([
            'equal' => 10000,
            'fraction' => 50000,
            'individual' => 0,
            'individualByUnit' => [],
        ]);
        // Act
        $slips = $this->factory->createFromExpensesAndUnits($allExpenses, [], $residentUnits, 2024, 5);

        // Assert
        $this->assertCount(2, $slips);
        $this->assertContainsOnlyInstancesOf(Slip::class, $slips);

        $amounts = [];
        foreach ($slips as $slip) {
            $amounts[$slip->residentUnit()->id()] = $slip->amount();
        }

        $this->assertEquals(27500, $amounts['resident-1']);
        $this->assertEquals(32500, $amounts['resident-2']);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function testFactorySkipsSlipForZeroAmountResident(): void
    {
        // Arrange (Rewritten for clarity)
        $typeFraction = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'FRACTION']);
        $allExpenses = [
            $this->createConfiguredMock(Expense::class, ['amount' => 50000, 'type' => $typeFraction])
        ];

        $residentWithZeroAmount = $this->createConfiguredMock(ResidentUnit::class, ['id' => 'resident-zero', 'idealFraction' => 0.0, 'unit' => '101']);
        $residentWithAmount = $this->createConfiguredMock(ResidentUnit::class, ['id' => 'resident-ok', 'idealFraction' => 0.10, 'unit' => '201']);

        $residentUnits = [$residentWithZeroAmount, $residentWithAmount];

        // Configura los mocks de los servicios internos de SlipFactory
        $this->monthlyExpenseAggregatorService->method('aggregateTotals')->willReturn([
            'equal' => 0,
            'fraction' => 50000,
            'individual' => 0,
            'individualByUnit' => [],
        ]);
        // Act
        $slips = $this->factory->createFromExpensesAndUnits($allExpenses, [], $residentUnits, 2024, 5);

        // Assert
        $this->assertCount(1, $slips);
        $this->assertEquals('resident-ok', $slips[0]->residentUnit()->id());
        $this->assertEquals(50000, $slips[0]->amount());
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function testFactoryReturnsEmptyArrayWhenNoExpenses(): void
    {
        // Arrange
        $resident = $this->createMock(ResidentUnit::class);
        $this->monthlyExpenseAggregatorService->method('aggregateTotals')->willReturn([
            'equal' => 0,
            'fraction' => 0,
            'individual' => 0,
            'individualByUnit' => [],
            'grandTotal' => 0,
        ]);
        // Act
        $slips = $this->factory->createFromExpensesAndUnits([], [], [$resident], 2024, 5);

        // Assert
        $this->assertEmpty($slips);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function testFactoryReturnsEmptyArrayWhenNoResidents(): void
    {
        // Arrange
        $expense = $this->createMock(Expense::class);

        // Act
        $slips = $this->factory->createFromExpensesAndUnits([$expense], [], [], 2024, 5);

        // Assert
        $this->assertEmpty($slips);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function testFactorySetsCorrectDueDate(): void
    {
        // Arrange
        $typeEqual = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'EQUAL']);
        $allExpenses = [$this->createConfiguredMock(Expense::class, ['amount' => 10000, 'type' => $typeEqual])];
        $resident = $this->createConfiguredMock(ResidentUnit::class, ['id' => 'resident-1', 'idealFraction' => 0.0, 'unit' => '101']);

        // Configura los mocks de los servicios internos de SlipFactory
        $this->monthlyExpenseAggregatorService->method('aggregateTotals')->willReturn([
            'equal' => 10000,
            'fraction' => 0,
            'individual' => 0,
            'individualByUnit' => [],
        ]);
        // Act: Generate for November 2024. Due date should be 5th business day of December 2024.
        $slips = $this->factory->createFromExpensesAndUnits($allExpenses, [], [$resident], 2024, 11);

        // Assert
        $this->assertCount(1, $slips);
        $dueDate = $slips[0]->dueDate();

        $this->assertInstanceOf(DateTimeImmutable::class, $dueDate);
        // Dec 1, 2024 is a Sunday. 5th business day is Friday, Dec 6, 2024.
        $this->assertEquals('2024-12-06', $dueDate->format('Y-m-d'));
    }
}