<?php

declare(strict_types=1);

namespace App\Context\BillingPolicy\Domain;

interface BillingPolicyResolverPort
{
    public function resolve(string $targetMonth): ResolvedBillingPolicy;
}
