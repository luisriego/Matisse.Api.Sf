<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\SendSlip;

use App\Shared\Application\Command;

final readonly class SlipSendCommand implements Command
{
    public function __construct(public string $id) {}

    public function id(): string
    {
        return $this->id();
    }
}
