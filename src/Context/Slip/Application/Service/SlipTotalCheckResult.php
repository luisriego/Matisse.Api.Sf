<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\Service;

final class SlipTotalCheckResult
{
    public function __construct(
        public readonly string $status,
        public readonly string $message,
        public readonly int $amount,
    ) {}
}
