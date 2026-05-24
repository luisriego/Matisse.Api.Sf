<?php

declare(strict_types=1);

namespace App\Context\Setup\Application\UseCase\PreviewInitialBalances;

use App\Shared\Application\Query;

/**
 * @param array<array{accountId: string, amountCents: int}> $balances
 * @param list<string>                                       $adjustmentPriority Account IDs in absorption order
 */
final readonly class PreviewInitialBalancesQuery implements Query
{
    public function __construct(
        public string $cutoffDate,
        public int $confirmedBankBalanceCents,
        public array $balances,
        public array $adjustmentPriority,
    ) {}
}
