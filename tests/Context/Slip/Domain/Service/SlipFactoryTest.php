<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Domain\Service;

use App\Context\Condominium\Domain\CondominiumConfiguration;
use App\Context\Condominium\Domain\Service\CondominiumFundAmountService;
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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SlipFactoryTest extends TestCase
{
    private MockObject|CondominiumFundAmountService $condominiumFundAmountService;
    private MockObject|MonthlyExpenseAggregatorService $monthlyExpenseAggregatorService;
    private MockObject|SlipAmountCalculatorService $slipAmountCalculatorService;
    private MockObject|StoredEventRepository $storedEventRepository;
    private MockObject|LoggerInterface $logger;
    private MockObject|ExpenseTypeRepository $expenseTypeRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // Create all the mocks
        $this->monthlyExpenseAggregatorService = $this->createMock(MonthlyExpenseAggregatorService::class);
        $this->slipAmountCalculatorService = $this->createMock(SlipAmountCalculatorService::class);
        $this->storedEventRepository = $this->createMock(StoredEventRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->expenseTypeRepository = $this->createMock(ExpenseTypeRepository::class);
        $this->condominiumFundAmountService = $this->createMock(CondominiumFundAmountService::class);

        // --- Default mock configurations (can be overridden in tests) ---

        // Default for gas expense type
        $gasExpenseType = $this->createConfiguredMock(ExpenseType::class, ['id' => 'gas-expense-type-id']);
        $this->expenseTypeRepository->method('findOneByCodeOrFail')->willReturn($gasExpenseType);

        // Default for gas events (no events)
        $this->storedEventRepository->method('findByEventNamesAndOccurredBetween')->willReturn([]);

        // Default for fund amounts (return 0 for both funds)
        $defaultCondoConfig = $this->createMock(CondominiumConfiguration::class);
        $defaultCondoConfig->method('reserveFundAmount')->willReturn(0);
        $defaultCondoConfig->method('constructionFundAmount')->willReturn(0);
        // No configuramos el willReturn aquí, se hará en cada test si se espera una llamada.
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

        // --- Specific mock configurations for this test ---

        // Configure fund amounts for this test
        $condoConfig = $this->createMock(CondominiumConfiguration::class);
        $condoConfig->method('reserveFundAmount')->willReturn(1000);
        $condoConfig->method('constructionFundAmount')->willReturn(500);

        // Aseguramos que getActiveConfigurationForDate sea llamado y devuelva nuestro mock configurado
        $this->condominiumFundAmountService->expects($this->once())
            ->method('getActiveConfigurationForDate')
            ->willReturn($condoConfig);

        // Configure aggregator service
        $this->monthlyExpenseAggregatorService->method('aggregateTotals')->willReturn([
            'equal' => 10000,
            'fraction' => 50000,
            'individual' => 0,
        ]);

        // Configure calculator service
        $this->slipAmountCalculatorService->method('calculate')
            ->willReturnMap([
                [$resident1, 10000, 50000, 2, 8500], // Base calculation: (10000 / 2) + (50000 * 0.07) = 5000 + 3500
                [$resident2, 10000, 50000, 2, 10000], // Base calculation: (10000 / 2) + (50000 * 0.10) = 5000 + 5000
            ]);

        // Instantiate the factory AFTER all specific mock configurations
        $factory = new SlipFactory(
            $this->monthlyExpenseAggregatorService,
            $this->slipAmountCalculatorService,
            $this->storedEventRepository,
            $this->logger,
            $this->expenseTypeRepository,
            $this->condominiumFundAmountService
        );

        // Act
        $slips = $factory->createFromExpensesAndUnits($allExpenses, $residentUnits, 2024, 5);

        // Assert
        $this->assertCount(2, $slips);
        $this->assertContainsOnlyInstancesOf(Slip::class, $slips);

        $amounts = [];
        foreach ($slips as $slip) {
            $amounts[$slip->residentUnit()->id()] = $slip->amount();
        }

        // Assert final amounts including funds
        // Resident 1: 8500 (base) + 1000 (reserve) + 500 (construction) = 10000
        $this->assertEquals(10000, $amounts['resident-1']);
        // Resident 2: 10000 (base) + 1000 (reserve) + 500 (construction) = 11500
        $this->assertEquals(11500, $amounts['resident-2']);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function testFactorySkipsSlipForZeroAmountResident(): void
    {
        // Arrange
        $typeFraction = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'FRACTION']);
        $allExpenses = [
            $this->createConfiguredMock(Expense::class, ['amount' => 50000, 'type' => $typeFraction])
        ];

        // Resident 1 has 0 fraction, so their calculated amount will be 0
        $residentWithZeroAmount = $this->createConfiguredMock(ResidentUnit::class, ['id' => 'resident-zero', 'idealFraction' => 0.0]);
        // Resident 2 has a fraction, so their amount will be > 0
        $residentWithAmount = $this->createConfiguredMock(ResidentUnit::class, ['id' => 'resident-ok', 'idealFraction' => 0.10]);

        $residentUnits = [$residentWithZeroAmount, $residentWithAmount];

        // --- Specific mock configurations for this test ---

        // Ensure getActiveConfigurationForDate is called and returns the default 0,0 config
        $defaultCondoConfig = $this->createMock(CondominiumConfiguration::class);
        $defaultCondoConfig->method('reserveFundAmount')->willReturn(0);
        $defaultCondoConfig->method('constructionFundAmount')->willReturn(0);
        $this->condominiumFundAmountService->expects($this->once())
            ->method('getActiveConfigurationForDate')
            ->willReturn($defaultCondoConfig);

        // Configure aggregator service
        $this->monthlyExpenseAggregatorService->method('aggregateTotals')->willReturn([
            'equal' => 0,
            'fraction' => 50000,
            'individual' => 0,
        ]);

        // Configure calculator service
        $this->slipAmountCalculatorService->method('calculate')
            ->willReturnMap([
                [$residentWithZeroAmount, 0, 50000, 2, 0], // Base calculation is 0
                [$residentWithAmount, 0, 50000, 2, 5000], // Base calculation: 50000 * 0.10
            ]);

        // Instantiate the factory AFTER all specific mock configurations
        $factory = new SlipFactory(
            $this->monthlyExpenseAggregatorService,
            $this->slipAmountCalculatorService,
            $this->storedEventRepository,
            $this->logger,
            $this->expenseTypeRepository,
            $this->condominiumFundAmountService
        );

        // Act
        $slips = $factory->createFromExpensesAndUnits($allExpenses, $residentUnits, 2024, 5);

        // Assert
        // The resident with a base calculation of 0 should be skipped because the total amount (0 + 0 + 0) is not > 0.
        // The resident with a base calculation of 5000 should have a slip.
        $this->assertCount(1, $slips);
        $this->assertEquals('resident-ok', $slips[0]->residentUnit()->id());
        $this->assertEquals(5000, $slips[0]->amount()); // 5000 (base) + 0 (reserve) + 0 (construction)
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function testFactoryReturnsEmptyArrayWhenNoExpenses(): void
    {
        // Arrange
        $resident = $this->createMock(ResidentUnit::class);

        // Ensure getActiveConfigurationForDate is NOT called
        $this->condominiumFundAmountService->expects($this->never())
            ->method('getActiveConfigurationForDate');

        // Instantiate the factory AFTER all specific mock configurations (none needed here, defaults are fine)
        $factory = new SlipFactory(
            $this->monthlyExpenseAggregatorService,
            $this->slipAmountCalculatorService,
            $this->storedEventRepository,
            $this->logger,
            $this->expenseTypeRepository,
            $this->condominiumFundAmountService
        );

        // Act
        $slips = $factory->createFromExpensesAndUnits([], [$resident], 2024, 5);

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

        // Ensure getActiveConfigurationForDate is NOT called
        $this->condominiumFundAmountService->expects($this->never())
            ->method('getActiveConfigurationForDate');

        // Instantiate the factory AFTER all specific mock configurations (none needed here, defaults are fine)
        $factory = new SlipFactory(
            $this->monthlyExpenseAggregatorService,
            $this->slipAmountCalculatorService,
            $this->storedEventRepository,
            $this->logger,
            $this->expenseTypeRepository,
            $this->condominiumFundAmountService
        );

        // Act
        $slips = $factory->createFromExpensesAndUnits([$expense], [], 2024, 5);

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

        // --- Specific mock configurations for this test ---

        // Ensure getActiveConfigurationForDate is called and returns the default 0,0 config
        $defaultCondoConfig = $this->createMock(CondominiumConfiguration::class);
        $defaultCondoConfig->method('reserveFundAmount')->willReturn(0);
        $defaultCondoConfig->method('constructionFundAmount')->willReturn(0);
        $this->condominiumFundAmountService->expects($this->once())
            ->method('getActiveConfigurationForDate')
            ->willReturn($defaultCondoConfig);

        // Configure aggregator service
        $this->monthlyExpenseAggregatorService->method('aggregateTotals')->willReturn([
            'equal' => 10000,
            'fraction' => 0,
            'individual' => 0,
        ]);

        // Configure calculator service (assuming one resident, so base is 10000)
        $this->slipAmountCalculatorService->method('calculate')->willReturn(10000);

        // Instantiate the factory AFTER all specific mock configurations
        $factory = new SlipFactory(
            $this->monthlyExpenseAggregatorService,
            $this->slipAmountCalculatorService,
            $this->storedEventRepository,
            $this->logger,
            $this->expenseTypeRepository,
            $this->condominiumFundAmountService
        );

        // Act: Generate for November 2024. Due date should be 5th business day of December 2024.
        $slips = $factory->createFromExpensesAndUnits($allExpenses, [$resident], 2024, 11);

        // Assert
        $this->assertCount(1, $slips);
        $dueDate = $slips[0]->dueDate();

        $this->assertInstanceOf(DateTimeImmutable::class, $dueDate);
        // Dec 1, 2024 is a Sunday. 5th business day is Friday, Dec 6, 2024.
        $this->assertEquals('2024-12-06', $dueDate->format('Y-m-d'));
    }
}