<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Domain;

/**
 * Ideal fractions must sum to 1, but floating-point accumulation may produce
 * values such as 1.00000005 — those are accepted within {@see TOLERANCE}.
 */
final class IdealFractionSumPolicy
{
    public const MAX_TOTAL = 1.0;

    /** e.g. 1.00000005 is treated as 1.0 */
    public const TOLERANCE = 1.0e-5;

    public static function exceedsMaximum(float $total): bool
    {
        return $total > self::MAX_TOTAL + self::TOLERANCE;
    }

    public static function isWithinMaximum(float $accumulated, float $additional): bool
    {
        return !self::exceedsMaximum($accumulated + $additional);
    }
}
