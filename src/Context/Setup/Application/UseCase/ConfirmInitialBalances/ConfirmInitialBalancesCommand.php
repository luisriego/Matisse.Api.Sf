<?php

declare(strict_types=1);

namespace App\Context\Setup\Application\UseCase\ConfirmInitialBalances;

use App\Shared\Application\Command;

/**
 * @param array<array{accountId: string, amountCents: int}> $balances
 * @param list<string>                                      $adjustmentPriority Account IDs in absorption order
 */
final readonly class ConfirmInitialBalancesCommand implements Command
{
    public function __construct(
        public string $cutoffDate,
        public int $confirmedBankBalanceCents,
        public array $balances,
        public array $adjustmentPriority,
    ) {}
}
