<?php

namespace App\Context\Slip\Application\UseCase\CheckSlipTotalAnomaly;

use App\Context\Slip\Application\Service\SlipAlertService;
use App\Shared\Application\CommandHandler;

class CheckSlipTotalAnomalyCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly SlipAlertService $slipAlertService
    ) {}

    public function __invoke(CheckSlipTotalAnomalyCommand $command): ?string
    {
        return $this->slipAlertService->checkAndGenerateAnomalyAlert(
            $command->getAmount()
        );
    }
}
    