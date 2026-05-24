<?php

declare(strict_types=1);

namespace App\Context\BillingPolicy\Application\UseCase\ResolveBillingPolicy;

use App\Context\BillingPolicy\Application\Service\BillingPolicyResolverService;
use App\Context\BillingPolicy\Domain\ResolvedBillingPolicy;
use App\Shared\Application\QueryHandler;

final readonly class ResolveBillingPolicyQueryHandler implements QueryHandler
{
    public function __construct(private BillingPolicyResolverService $billingPolicyResolverService) {}

    public function __invoke(ResolveBillingPolicyQuery $query): ResolvedBillingPolicy
    {
        return $this->billingPolicyResolverService->resolve($query->targetMonth());
    }
}
