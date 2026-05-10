<?php

declare(strict_types=1);

namespace App\Context\Setup\Domain;

/**
 * Fixed aggregate id for setup milestones that belong to the single condominium (single-tenant DB).
 */
final class OpeningSetupAggregateId
{
    public const VALUE = 'a0000001-0000-4000-8000-000000000001';

    private function __construct() {}
}
