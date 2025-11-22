<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\GetSlipDetails;

use App\Shared\Application\Query;

final readonly class GetSlipDetailsQuery implements Query
{
    public function __construct(
        public string $slipId
    ) {
    }
}
