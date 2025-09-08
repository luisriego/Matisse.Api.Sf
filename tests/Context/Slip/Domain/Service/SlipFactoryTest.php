<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Domain\Service;

use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\Slip\Domain\Service\MonthlyExpenseAggregatorService;
use App\Context\Slip\Domain\Service\SlipAmountCalculatorService;
use App\Context\Slip\Domain\Service\SlipFactory;
use App\Context\Slip\Domain\Slip;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SlipFactoryTest extends TestCase
{
    private SlipFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $logger = $this->createMock(LoggerInterface::class);

        // Use real instances of the services to test their integration
        $aggregator = new MonthlyExpenseAggregatorService($logger);
        $calculator = new SlipAmountCalculatorService($logger);

        $this->factory = new SlipFactory($aggregator, $calculator, $logger);
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

        // Act
        $slips = $this->factory->createFromExpensesAndUnits($allExpenses, $residentUnits, 2024, 5);

        // Assert
        $this->assertCount(2, $slips);
        $this->assertContainsOnlyInstancesOf(Slip::class, $slips);

        $amounts = [];
        foreach ($slips as $slip) {
            // FIX: Call amount() directly, assuming it returns an int
            $amounts[$slip->residentUnit()->id()] = $slip->amount();
        }

        $this->assertEquals(8500, $amounts['resident-1']); // (10000 / 2) + (50000 * 0.07) = 5000 + 3500
        $this->assertEquals(10000, $amounts['resident-2']); // (10000 / 2) + (50000 * 0.10) = 5000 + 5000
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

        // Resident 1 has 0 fraction, so their calculated amount will be 0
        $residentWithZeroAmount = $this->createConfiguredMock(ResidentUnit::class, ['id' => 'resident-zero', 'idealFraction' => 0.0]);
        // Resident 2 has a fraction, so their amount will be > 0
        $residentWithAmount = $this->createConfiguredMock(ResidentUnit::class, ['id' => 'resident-ok', 'idealFraction' => 0.10]);

        $residentUnits = [$residentWithZeroAmount, $residentWithAmount];

        // Act
        $slips = $this->factory->createFromExpensesAndUnits($allExpenses, $residentUnits, 2024, 5);

        // Assert
        $this->assertCount(1, $slips);
        $this->assertEquals('resident-ok', $slips[0]->residentUnit()->id());
        // FIX: Call amount() directly
        $this->assertEquals(5000, $slips[0]->amount()); // 50000 * 0.10
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

        // Act: Generate for November 2024. Due date should be 5th business day of December 2024.
        $slips = $this->factory->createFromExpensesAndUnits($allExpenses, [$resident], 2024, 11);

        // Assert
        $this->assertCount(1, $slips);
        // FIX: Call dueDate() directly, assuming it returns DateTimeImmutable
        $dueDate = $slips[0]->dueDate();

        $this->assertInstanceOf(DateTimeImmutable::class, $dueDate);
        // Dec 1, 2024 is a Sunday. 5th business day is Friday, Dec 6, 2024.
        $this->assertEquals('2024-12-06', $dueDate->format('Y-m-d'));
    }
}
