<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Exception;

use RuntimeException;

use function sprintf;

final class PeriodAlreadyClosedException extends RuntimeException
{
    public static function forMonth(int $year, int $month): self
    {
        return new self(sprintf(
            'Period %04d-%02d is already closed. No modifications are allowed.',
            $year,
            $month,
        ));
    }
}
