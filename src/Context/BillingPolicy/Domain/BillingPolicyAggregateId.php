<?php

declare(strict_types=1);

namespace App\Context\BillingPolicy\Domain;

/**
 * Fixed aggregate id for monthly billing parameters (single-tenant DB).
 */
final class BillingPolicyAggregateId
{
    public const VALUE = 'a0000002-0000-4000-8000-000000000002';

    private function __construct() {}
}
