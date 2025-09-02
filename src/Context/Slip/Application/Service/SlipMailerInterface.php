<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\Service;

use App\Context\Slip\Application\Dto\SlipEmailDto;

interface SlipMailerInterface
{
    public function sendSlipSubmittedEmail(SlipEmailDto $slipData): void;
}
