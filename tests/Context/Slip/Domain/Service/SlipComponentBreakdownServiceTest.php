<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Domain\Service;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\Slip\Domain\Service\SlipComponentBreakdownService;
use PHPUnit\Framework\TestCase;

use function array_map;
use function array_sum;

final class SlipComponentBreakdownServiceTest extends TestCase
{
    private SlipComponentBreakdownService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SlipComponentBreakdownService();
    }

    public function testBuildKeepsComponentsAndUnitsConsistentForFiveUnits(): void
    {
        $units = [
            $this->unit('u-101', '101', 0.20),
            $this->unit('u-201', '201', 0.20),
            $this->unit('u-301', '301', 0.20),
            $this->unit('u-401', '401', 0.20),
            $this->unit('u-501', '501', 0.20),
        ];

        $result = $this->service->build(
            $units,
            0,
            60000,
            0,
            [],
            [
                'u-101' => 1000,
                'u-201' => 2000,
                'u-301' => 3000,
                'u-401' => 4000,
                'u-501' => 5000,
            ],
            25000,
            9370,
        );

        $this->assertSame(125000, $result['components']['extraTotalCents']);
        $this->assertSame(46850, $result['components']['reserveTotalCents']);
        $this->assertSame(60000, $result['components']['syndicTotalCents']);
        $this->assertSame(0, $result['totals']['differenceCents']);
        $this->assertSame(
            $result['totals']['totalCents'],
            array_sum(array_map(static fn (array $u): int => $u['totalCents'], $result['units'])),
        );

        foreach ($result['units'] as $unit) {
            $this->assertSame(
                $unit['baseCents'] + $unit['syndicCents'] + $unit['extraCents'] + $unit['reserveCents'] + $unit['gasCents'],
                $unit['totalCents'],
            );
        }
    }

    public function testRegressionMarch2026DemonstrativeValuesForDespesasPrevistas(): void
    {
        $units = [
            $this->unit('u-101', '101', 0.1813176),
            $this->unit('u-201', '201', 0.1813176),
            $this->unit('u-301', '301', 0.1813176),
            $this->unit('u-401', '401', 0.1985090),
            $this->unit('u-501', '501', 0.2578779),
        ];

        $result = $this->service->build(
            $units,
            298856,
            0,
            0,
            [
                'u-101' => 3946,
                'u-201' => 3946,
                'u-301' => 3946,
                'u-401' => 5110,
                'u-501' => 9232,
            ],
            [],
            0,
            0,
        );

        $despesasPrevistas = array_map(
            static fn (array $u): int => $u['equalShareCents'],
            $result['units'],
        );

        $this->assertSame([63718, 63718, 63718, 64882, 69004], $despesasPrevistas);
        $this->assertSame(325040, array_sum($despesasPrevistas));
        $this->assertSame($result['components']['baseTotalCents'], $result['components']['despesasPrevistasTotalCents']);
    }

    public function testPicoIsRaisedToOneCentForEveryUnit(): void
    {
        $units = [
            $this->unit('u-101', '101', 0.10),
            $this->unit('u-201', '201', 0.10),
            $this->unit('u-301', '301', 0.10),
            $this->unit('u-401', '401', 0.10),
            $this->unit('u-501', '501', 0.20),
        ];

        $equal = $this->service->build($units, 103, 0, 0, [], [], 0, 0);
        $equalShares = array_map(
            static fn (array $unit): int => $unit['baseEqualShareCents'],
            $equal['units'],
        );

        $this->assertSame([21, 21, 21, 21, 21], $equalShares);
        $this->assertSame(105, $equal['components']['baseTotalCents']);

        $fraction = $this->service->build($units, 0, 0, 10, [], [], 0, 0);
        $fractionShares = array_map(
            static fn (array $unit): int => $unit['fractionShareCents'],
            $fraction['units'],
        );

        $this->assertSame([2, 2, 2, 2, 4], $fractionShares);
        $this->assertSame(12, $fraction['components']['baseTotalCents']);
    }

    public function testExactDivisionDoesNotAddACent(): void
    {
        $units = [
            $this->unit('u-101', '101', 0.20),
            $this->unit('u-201', '201', 0.20),
            $this->unit('u-301', '301', 0.20),
            $this->unit('u-401', '401', 0.20),
            $this->unit('u-501', '501', 0.20),
        ];

        $result = $this->service->build($units, 100, 0, 0, [], [], 0, 0);
        $shares = array_map(
            static fn (array $unit): int => $unit['baseEqualShareCents'],
            $result['units'],
        );

        $this->assertSame([20, 20, 20, 20, 20], $shares);
        $this->assertSame(100, $result['components']['baseTotalCents']);
    }

    public function testLegacyDespesasPrevistasDoesNotIncludeSyndicPool(): void
    {
        $units = [
            $this->unit('u-101', '101', 0.20),
            $this->unit('u-201', '201', 0.20),
            $this->unit('u-301', '301', 0.20),
            $this->unit('u-401', '401', 0.20),
            $this->unit('u-501', '501', 0.20),
        ];

        $result = $this->service->build(
            $units,
            300000,
            60000,
            0,
            [],
            [],
            0,
            0,
        );

        $legacyPerUnit = array_map(static fn (array $u): int => $u['equalShareCents'], $result['units']);
        $this->assertSame(300000, array_sum($legacyPerUnit));
        $this->assertSame(300000, $result['components']['despesasPrevistasTotalCents']);
        $this->assertSame(60000, $result['components']['syndicTotalCents']);
    }

    public function testPf1seLinePassedWholeToEqualPoolWithoutSyndicSplit(): void
    {
        $units = [
            $this->unit('u-101', '101', 0.20),
            $this->unit('u-201', '201', 0.20),
            $this->unit('u-301', '301', 0.20),
            $this->unit('u-401', '401', 0.20),
            $this->unit('u-501', '501', 0.20),
        ];

        // Sin SyndicFeeSlipPoolAdjustmentService: 670 en pool igualitario (comportamiento bajo nivel).
        $result = $this->service->build(
            $units,
            67000,
            0,
            0,
            [],
            [],
            0,
            0,
        );

        $this->assertSame(67000, $result['components']['baseTotalCents']);
        $this->assertSame(0, $result['components']['syndicTotalCents']);
    }

    /** @phpstan-ignore-next-line */
    private function unit(string $id, string $unit, float $idealFraction): ResidentUnit
    {
        $mock = $this->createConfiguredMock(ResidentUnit::class, [
            'id' => $id,
            'unit' => $unit,
            'idealFraction' => $idealFraction,
        ]);

        return $mock;
    }
}
