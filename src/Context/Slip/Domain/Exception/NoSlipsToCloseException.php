<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Exception;

use RuntimeException;

use function sprintf;

final class NoSlipsToCloseException extends RuntimeException
{
    public static function forMonth(int $year, int $month): self
    {
        return new self(sprintf(
            'No slips found for period %04d-%02d. Cannot close an empty period.',
            $year,
            $month,
        ));
    }
}
