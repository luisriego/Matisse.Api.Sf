<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Domain\Service;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\Slip\Domain\Service\SlipAmountCalculatorService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SlipAmountCalculatorServiceTest extends TestCase
{
    private SlipAmountCalculatorService $calculator;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->calculator = new SlipAmountCalculatorService($this->logger);
    }

    public function testCalculateHappyPath(): void
    {
        // Arrange
        $resident = $this->createMock(ResidentUnit::class);
        $resident->method('idealFraction')->willReturn(0.07); // 7%
        $resident->method('unit')->willReturn('Apt 101');

        $totalEquallyDividedExpenses = 10000; // 100.00
        $totalFractionBasedExpenses = 50000;  // 500.00
        $numberOfPayingResidents = 2;

        // Act
        $amount = $this->calculator->calculate(
            $resident,
            $totalEquallyDividedExpenses,
            $totalFractionBasedExpenses,
            $numberOfPayingResidents
        );

        // Assert
        // Expected: (10000 / 2) + (50000 * 0.07) = 5000 + 3500 = 8500
        $this->assertEquals(8500, $amount);
    }

    public function testCalculateWithOnlyEqualExpenses(): void
    {
        // Arrange
        $resident = $this->createMock(ResidentUnit::class);
        $resident->method('idealFraction')->willReturn(0.07);

        $totalEquallyDividedExpenses = 10000; // 100.00
        $totalFractionBasedExpenses = 0;
        $numberOfPayingResidents = 2;

        // Act
        $amount = $this->calculator->calculate($resident, $totalEquallyDividedExpenses, $totalFractionBasedExpenses, $numberOfPayingResidents);

        // Assert
        // Expected: (10000 / 2) + 0 = 5000
        $this->assertEquals(5000, $amount);
    }

    public function testCalculateWithOnlyFractionExpenses(): void
    {
        // Arrange
        $resident = $this->createMock(ResidentUnit::class);
        $resident->method('idealFraction')->willReturn(0.07);

        $totalEquallyDividedExpenses = 0;
        $totalFractionBasedExpenses = 50000; // 500.00
        $numberOfPayingResidents = 2;

        // Act
        $amount = $this->calculator->calculate($resident, $totalEquallyDividedExpenses, $totalFractionBasedExpenses, $numberOfPayingResidents);

        // Assert
        // Expected: 0 + (50000 * 0.07) = 3500
        $this->assertEquals(3500, $amount);
    }

    public function testCalculateForResidentWithZeroIdealFraction(): void
    {
        // Arrange
        $resident = $this->createMock(ResidentUnit::class);
        $resident->method('idealFraction')->willReturn(0.0); // No fraction

        $totalEquallyDividedExpenses = 10000;
        $totalFractionBasedExpenses = 50000;
        $numberOfPayingResidents = 2;

        // Act
        $amount = $this->calculator->calculate($resident, $totalEquallyDividedExpenses, $totalFractionBasedExpenses, $numberOfPayingResidents);

        // Assert
        // Expected: (10000 / 2) + 0 = 5000
        $this->assertEquals(5000, $amount);
    }

    public function testCalculateWithZeroPayingResidents(): void
    {
        // Arrange
        $resident = $this->createMock(ResidentUnit::class);
        $resident->method('idealFraction')->willReturn(0.07);

        $totalEquallyDividedExpenses = 10000;
        $totalFractionBasedExpenses = 50000;
        $numberOfPayingResidents = 0; // Edge case

        // Act
        $amount = $this->calculator->calculate($resident, $totalEquallyDividedExpenses, $totalFractionBasedExpenses, $numberOfPayingResidents);

        // Assert
        // The equal part should be 0, only the fraction part is calculated
        // Expected: 0 + (50000 * 0.07) = 3500
        $this->assertEquals(3500, $amount);
    }

    public function testCalculateWithZeroExpenses(): void
    {
        // Arrange
        $resident = $this->createMock(ResidentUnit::class);
        $resident->method('idealFraction')->willReturn(0.07);

        $totalEquallyDividedExpenses = 0;
        $totalFractionBasedExpenses = 0;
        $numberOfPayingResidents = 2;

        // Act
        $amount = $this->calculator->calculate($resident, $totalEquallyDividedExpenses, $totalFractionBasedExpenses, $numberOfPayingResidents);

        // Assert
        $this->assertEquals(0, $amount);
    }

    public function testCalculateWithRoundingForEqualPart(): void
    {
        // Arrange
        $resident = $this->createMock(ResidentUnit::class);
        $resident->method('idealFraction')->willReturn(0.0);

        $totalEquallyDividedExpenses = 10001; // 100.01
        $numberOfPayingResidents = 3; // 10001 / 3 = 3333.666...

        // Act
        $amount = $this->calculator->calculate($resident, $totalEquallyDividedExpenses, 0, $numberOfPayingResidents);

        // Assert
        // Expected: round(3333.666...) = 3334
        $this->assertEquals(3334, $amount);
    }
}
