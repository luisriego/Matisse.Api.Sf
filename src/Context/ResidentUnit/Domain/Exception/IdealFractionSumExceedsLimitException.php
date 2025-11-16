<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Domain\Exception;

use DomainException;

final class IdealFractionSumExceedsLimitException extends DomainException
{
    public function __construct()
    {
        parent::__construct('The sum of ideal fractions cannot exceed 1.');
    }
}
