<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Domain\Service;

use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeRepository;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\Slip\Domain\Service\MonthlyExpenseAggregatorService;
use App\Context\Slip\Domain\Service\SlipAmountCalculatorService;
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
    private MockObject|SlipAmountCalculatorService $slipAmountCalculatorService; // Añadido
    private MockObject|StoredEventRepository $storedEventRepository; // Añadido
    private MockObject|LoggerInterface $logger; // Añadido
    private MockObject|ExpenseTypeRepository $expenseTypeRepository; // Añadido

    protected function setUp(): void
    {
        parent::setUp();

        // Inicializa los mocks para las dependencias de SlipFactory
        $this->monthlyExpenseAggregatorService = $this->createMock(MonthlyExpenseAggregatorService::class);
        $this->slipAmountCalculatorService = $this->createMock(SlipAmountCalculatorService::class);
        $this->storedEventRepository = $this->createMock(StoredEventRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->expenseTypeRepository = $this->createMock(ExpenseTypeRepository::class);

        // Configura el mock de expenseTypeRepository para findOneByCodeOrFail
        // Esto es crucial para evitar el TypeError en resolveGasExpenseTypeId
        $gasExpenseType = $this->createConfiguredMock(ExpenseType::class, ['id' => 'gas-expense-type-id']);
        $this->expenseTypeRepository->method('findOneByCodeOrFail')->with('SP3GA')->willReturn($gasExpenseType);
        // Configura un comportamiento por defecto para findOneByIdOrFail si es necesario
        $this->expenseTypeRepository->method('findOneByIdOrFail')->willReturn($this->createMock(ExpenseType::class));


        // Configura el mock de storedEventRepository para que no devuelva eventos de gas por defecto
        $this->storedEventRepository->method('findByEventNamesAndOccurredBetween')->willReturn([]);

        // Instancia SlipFactory con los mocks correctos
        $this->factory = new SlipFactory(
            $this->monthlyExpenseAggregatorService,
            $this->slipAmountCalculatorService,
            $this->storedEventRepository,
            $this->logger,
            $this->expenseTypeRepository
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

        $resident1 = $this->createConfiguredMock(ResidentUnit::class, ['id' => 'resident-1', 'idealFraction' => 0.07]);
        $resident2 = $this->createConfiguredMock(ResidentUnit::class, ['id' => 'resident-2', 'idealFraction' => 0.10]);
        $residentUnits = [$resident1, $resident2];

        // Configura los mocks de los servicios internos de SlipFactory
        $this->monthlyExpenseAggregatorService->method('aggregateTotals')->willReturn([
            'equal' => 10000,
            'fraction' => 50000,
            'individual' => 0,
        ]);
        $this->slipAmountCalculatorService->method('calculate')
            ->willReturnMap([
                [$resident1, 10000, 50000, 2, 8500], // (10000 / 2) + (50000 * 0.07) = 5000 + 3500
                [$resident2, 10000, 50000, 2, 10000], // (10000 / 2) + (50000 * 0.10) = 5000 + 5000
            ]);

        // Act
        $slips = $this->factory->createFromExpensesAndUnits($allExpenses, $residentUnits, 2024, 5);

        // Assert
        $this->assertCount(2, $slips);
        $this->assertContainsOnlyInstancesOf(Slip::class, $slips);

        $amounts = [];
        foreach ($slips as $slip) {
            $amounts[$slip->residentUnit()->id()] = $slip->amount();
        }

        $this->assertEquals(8500, $amounts['resident-1']);
        $this->assertEquals(10000, $amounts['resident-2']);
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

        $residentWithZeroAmount = $this->createConfiguredMock(ResidentUnit::class, ['id' => 'resident-zero', 'idealFraction' => 0.0]);
        $residentWithAmount = $this->createConfiguredMock(ResidentUnit::class, ['id' => 'resident-ok', 'idealFraction' => 0.10]);

        $residentUnits = [$residentWithZeroAmount, $residentWithAmount];

        // Configura los mocks de los servicios internos de SlipFactory
        $this->monthlyExpenseAggregatorService->method('aggregateTotals')->willReturn([
            'equal' => 0,
            'fraction' => 50000,
            'individual' => 0,
        ]);
        $this->slipAmountCalculatorService->method('calculate')
            ->willReturnMap([
                [$residentWithZeroAmount, 0, 50000, 2, 0],
                [$residentWithAmount, 0, 50000, 2, 5000], // 50000 * 0.10
            ]);

        // Act
        $slips = $this->factory->createFromExpensesAndUnits($allExpenses, $residentUnits, 2024, 5);

        // Assert
        $this->assertCount(1, $slips);
        $this->assertEquals('resident-ok', $slips[0]->residentUnit()->id());
        $this->assertEquals(5000, $slips[0]->amount());
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function testFactoryReturnsEmptyArrayWhenNoExpenses(): void
    {
        // Arrange
        $resident = $this->createMock(ResidentUnit::class);

        // Act
        $slips = $this->factory->createFromExpensesAndUnits([], [$resident], 2024, 5);

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
        $slips = $this->factory->createFromExpensesAndUnits([$expense], [], 2024, 5);

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
        $resident = $this->createConfiguredMock(ResidentUnit::class, ['id' => 'resident-1', 'idealFraction' => 0.0]);

        // Configura los mocks de los servicios internos de SlipFactory
        $this->monthlyExpenseAggregatorService->method('aggregateTotals')->willReturn([
            'equal' => 10000,
            'fraction' => 0,
            'individual' => 0,
        ]);
        $this->slipAmountCalculatorService->method('calculate')->willReturn(5000); // 10000 / 2

        // Act: Generate for November 2024. Due date should be 5th business day of December 2024.
        $slips = $this->factory->createFromExpensesAndUnits($allExpenses, [$resident], 2024, 11);

        // Assert
        $this->assertCount(1, $slips);
        $dueDate = $slips[0]->dueDate();

        $this->assertInstanceOf(DateTimeImmutable::class, $dueDate);
        // Dec 1, 2024 is a Sunday. 5th business day is Friday, Dec 6, 2024.
        $this->assertEquals('2024-12-06', $dueDate->format('Y-m-d'));
    }
}