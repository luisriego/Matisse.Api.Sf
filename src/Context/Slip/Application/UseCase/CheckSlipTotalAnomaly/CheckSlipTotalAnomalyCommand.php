<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\CheckSlipTotalAnomaly;

use App\Shared\Application\Query;

final readonly class CheckSlipTotalAnomalyCommand implements Query
{
    public function __construct(
        private int $amount,
    ) {}

    public function amount(): int
    {
        return $this->amount;
    }
}
