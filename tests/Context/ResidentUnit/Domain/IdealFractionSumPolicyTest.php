<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Domain;

use App\Context\ResidentUnit\Domain\IdealFractionSumPolicy;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Context\ResidentUnit\Domain\IdealFractionSumPolicy
 */
final class IdealFractionSumPolicyTest extends TestCase
{
    public function testItAcceptsFloatingPointOvershootWithinTolerance(): void
    {
        self::assertFalse(IdealFractionSumPolicy::exceedsMaximum(1.00000005));
        self::assertTrue(IdealFractionSumPolicy::isWithinMaximum(0.99999995, 0.0000001));
    }

    public function testItRejectsMeaningfulOvershoot(): void
    {
        self::assertTrue(IdealFractionSumPolicy::exceedsMaximum(1.0001));
        self::assertFalse(IdealFractionSumPolicy::isWithinMaximum(0.9, 0.2));
    }
}
