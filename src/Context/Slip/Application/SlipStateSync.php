<?php

declare(strict_types=1);

namespace App\Context\Slip\Application;

use App\Context\Slip\Domain\SlipStatus;

use function mb_strtoupper;

final class SlipStateSync
{
    public static function syncEnum(object $slip): void
    {
        $place = $slip->getCurrentPlace();
        $slip->setStatus(SlipStatus::from(mb_strtoupper($place)));
    }
}
