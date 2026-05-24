<?php

declare(strict_types=1);

namespace App\Tests\Context\BillingPolicy\Domain\Service;

use App\Context\BillingPolicy\Domain\Service\BillingPolicyResolver;
use App\Context\BillingPolicy\Domain\ValueObject\BillingPolicySnapshot;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BillingPolicyResolverTest extends TestCase
{
    private BillingPolicyResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new BillingPolicyResolver();
    }

    public function testReturnsExplicitSnapshotWhenTargetMonthExists(): void
    {
        $snapshots = [
            '2026-01' => $this->snapshot('2026-01', 25000, 9370, 60000, 2600),
        ];

        $resolved = $this->resolver->resolve($snapshots, '2026-01');

        self::assertTrue($resolved->explicit());
        self::assertSame('2026-01', $resolved->sourceMonth());
        self::assertSame(25000, $resolved->extraFeePerUnitCents());
        self::assertSame(9370, $resolved->reserveFundPerUnitCents());
        self::assertSame(2600, $resolved->gasPricePerM3Cents());
        self::assertSame('equal_parts', $resolved->syndicAllocationRule());
    }

    public function testInheritsFromLatestPriorMonth(): void
    {
        $snapshots = [
            '2026-01' => $this->snapshot('2026-01', 25000, 9370, 60000, 2600),
        ];

        $resolved = $this->resolver->resolve($snapshots, '2026-03');

        self::assertFalse($resolved->explicit());
        self::assertSame('2026-01', $resolved->sourceMonth());
        self::assertSame(25000, $resolved->extraFeePerUnitCents());
    }

    public function testReturnsEmptyDefaultsWhenNoPriorSnapshots(): void
    {
        $resolved = $this->resolver->resolve([], '2026-05');

        self::assertNull($resolved->sourceMonth());
        self::assertSame(60000, $resolved->syndicShareTotalCents());
        self::assertSame(0, $resolved->extraFeePerUnitCents());
        self::assertSame(0, $resolved->reserveFundPerUnitCents());
        self::assertNull($resolved->gasPricePerM3Cents());
    }

    private function snapshot(
        string $targetMonth,
        int $extraFee,
        int $reserveFund,
        int $syndicShare,
        ?int $gasPrice,
    ): BillingPolicySnapshot {
        return new BillingPolicySnapshot(
            $targetMonth,
            $extraFee,
            $reserveFund,
            $syndicShare,
            $gasPrice,
            new DateTimeImmutable('2026-01-01T00:00:00Z'),
        );
    }
}
