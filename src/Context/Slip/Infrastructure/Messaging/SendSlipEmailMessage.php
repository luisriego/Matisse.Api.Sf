<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Messaging;

use App\Shared\Application\AsyncMessage;

final readonly class SendSlipEmailMessage implements AsyncMessage
{
    public function __construct(
        private string $residentUnitId,
        private string $slipId,
        private int $amount,
        private string $dueDate,
    ) {
    }

    public function residentUnitId(): string
    {
        return $this->residentUnitId;
    }

    public function slipId(): string
    {
        return $this->slipId;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function dueDate(): string
    {
        return $this->dueDate;
    }
}
