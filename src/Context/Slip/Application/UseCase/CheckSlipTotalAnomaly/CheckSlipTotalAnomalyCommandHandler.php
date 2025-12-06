<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\CheckSlipTotalAnomaly;

use App\Context\Slip\Application\Service\SlipAlertContentProvider;
use App\Context\Slip\Domain\Service\SlipAnomalyDetector;
use App\Context\Slip\Domain\ValueObject\SlipAmount;
use App\Shared\Application\QueryHandler;

final class CheckSlipTotalAnomalyCommandHandler implements QueryHandler
{
    public function __construct(
        private readonly SlipAnomalyDetector $anomalyDetector,
        private readonly SlipAlertContentProvider $alertContentProvider,
    ) {}

    public function __invoke(CheckSlipTotalAnomalyCommand $command): array
    {
        $amount = new SlipAmount($command->amount());

        if (!$this->anomalyDetector->isAnomaly($amount)) {
            return [
                'status' => 'ok',
                'message' => 'O total do slip está dentro do intervalo esperado.',
                'amount' => $amount->value(),
            ];
        }

        $anomalyType = $this->anomalyDetector->getAnomalyType($amount);

        $alertMessage = $this->alertContentProvider->provide(
            $anomalyType,
            $command->amount(),
            $this->anomalyDetector->getMinExpectedAmount()->value(),
            $this->anomalyDetector->getMaxExpectedAmount()->value(),
        );

        return [
            'status' => 'alert_generated',
            'message' => $alertMessage,
            'amount' => $amount->value(),
        ];
    }
}
