<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\Message;

use App\Shared\Application\AsyncMessage;

final readonly class SendSlipNotification implements AsyncMessage
{
    public function __construct(
        public string $slipId,
        public string $residentUnitId,
        public int $amount,
        public string $dueDate
    ) {}
}
