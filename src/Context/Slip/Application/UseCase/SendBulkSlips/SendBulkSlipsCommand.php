<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\SendBulkSlips;

use App\Shared\Application\Command;

final readonly class SendBulkSlipsCommand implements Command
{
    /**
     * @param string[] $slipIds
     */
    public function __construct(
        public array $slipIds,
    ) {}
}
