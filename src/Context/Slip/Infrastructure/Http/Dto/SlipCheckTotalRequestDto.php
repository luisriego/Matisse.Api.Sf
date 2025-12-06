<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Dto;

use App\Context\Slip\Application\UseCase\CheckSlipTotalAnomaly\CheckSlipTotalAnomalyCommand;

final readonly class SlipCheckTotalRequestDto
{
    public function __construct(
        public int $amount,
    ) {}

    public function toCommand(): CheckSlipTotalAnomalyCommand
    {
        return new CheckSlipTotalAnomalyCommand($this->amount);
    }
}
