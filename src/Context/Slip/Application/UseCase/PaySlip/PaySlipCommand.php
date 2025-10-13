<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\PaySlip;

use App\Shared\Application\Command;

final readonly class PaySlipCommand implements Command
{
    public function __construct(public string $id) {}

    public function id(): string
    {
        return $this->id;
    }
}
