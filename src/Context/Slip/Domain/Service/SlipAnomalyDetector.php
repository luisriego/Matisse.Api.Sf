<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Service;

use App\Context\Slip\Domain\ValueObject\SlipAmount;

final class SlipAnomalyDetector
{
    private const MIN_EXPECTED_AMOUNT = 500000;
    private const MAX_EXPECTED_AMOUNT = 1000000;

    public function isAnomaly(SlipAmount $amount): bool
    {
        return $amount->value() < self::MIN_EXPECTED_AMOUNT || $amount->value() > self::MAX_EXPECTED_AMOUNT;
    }

    public function getAnomalyType(SlipAmount $amount): string
    {
        return $amount->value() < self::MIN_EXPECTED_AMOUNT ? 'muito baixo' : 'muito alto';
    }

    public function getMinExpectedAmount(): SlipAmount
    {
        return new SlipAmount(self::MIN_EXPECTED_AMOUNT);
    }

    public function getMaxExpectedAmount(): SlipAmount
    {
        return new SlipAmount(self::MAX_EXPECTED_AMOUNT);
    }
}
