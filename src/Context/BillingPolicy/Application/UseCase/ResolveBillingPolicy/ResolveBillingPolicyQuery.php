<?php

declare(strict_types=1);

namespace App\Context\BillingPolicy\Application\UseCase\ResolveBillingPolicy;

use App\Shared\Application\Query;

final readonly class ResolveBillingPolicyQuery implements Query
{
    public function __construct(private string $targetMonth) {}

    public function targetMonth(): string
    {
        return $this->targetMonth;
    }
}
