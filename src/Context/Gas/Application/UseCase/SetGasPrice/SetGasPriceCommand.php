<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\UseCase\SetGasPrice;

use App\Shared\Application\Command;

final readonly class SetGasPriceCommand implements Command
{
    public function __construct(
        public int $pricePerM3InCents,
    ) {}
}
