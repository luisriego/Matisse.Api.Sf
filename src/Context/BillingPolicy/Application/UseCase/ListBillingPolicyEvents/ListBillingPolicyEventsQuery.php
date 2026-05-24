<?php

declare(strict_types=1);

namespace App\Context\BillingPolicy\Application\UseCase\ListBillingPolicyEvents;

use App\Shared\Application\Query;

final readonly class ListBillingPolicyEventsQuery implements Query
{
    public function __construct(private int $limit = 50) {}

    public function limit(): int
    {
        return $this->limit;
    }
}
