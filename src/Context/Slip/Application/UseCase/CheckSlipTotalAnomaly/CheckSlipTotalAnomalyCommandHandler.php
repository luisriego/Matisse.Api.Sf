<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\CheckSlipTotalAnomaly;

use App\Context\Slip\Application\Service\SlipAlertService;
use App\Shared\Application\CommandHandler;
use stdClass;

class CheckSlipTotalAnomalyCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly SlipAlertService $slipAlertService,
    ) {}

    public function __invoke(CheckSlipTotalAnomalyCommand $command): ?stdClass
    {
        return $this->slipAlertService->checkAndGenerateAnomalyAlert(
            $command->getAmount(),
        );
    }
}
