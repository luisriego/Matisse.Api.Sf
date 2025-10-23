<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\CheckSlipTotalAnomaly;

use App\Context\Slip\Domain\ValueObject\SlipAmount;

class CheckSlipTotalAnomalyCommand
{
    public function __construct(
        private SlipAmount $amount // <-- El constructor DEBE esperar un objeto SlipAmount
    ) {
    }

    public function getAmount(): SlipAmount // <-- El método DEBE devolver un objeto SlipAmount
    {
        return $this->amount;
    }
}