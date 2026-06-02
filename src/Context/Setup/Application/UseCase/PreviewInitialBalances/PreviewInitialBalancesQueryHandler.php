<?php

declare(strict_types=1);

namespace App\Context\Setup\Application\UseCase\PreviewInitialBalances;

use App\Shared\Application\QueryHandler;
use App\Shared\Domain\Exception\InvalidDataException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use function array_column;
use function array_sum;
use function array_values;
use function sprintf;

#[AsMessageHandler(bus: 'query.bus')]
final class PreviewInitialBalancesQueryHandler implements QueryHandler
{
    public function __invoke(PreviewInitialBalancesQuery $query): array
    {
        $balances    = $this->indexByAccountId($query->balances);
        $sumCents    = array_sum(array_column($query->balances, 'amountCents'));
        $discrepancy = $query->confirmedBankBalanceCents - $sumCents;

        if ($discrepancy !== 0) {
            $balances = $this->absorb($balances, $discrepancy, $query->adjustmentPriority);
        }

        return [
            'cutoffDate'              => $query->cutoffDate,
            'confirmedBankBalanceCents' => $query->confirmedBankBalanceCents,
            'proposedSumCents'        => $sumCents,
            'discrepancyCents'        => $discrepancy,
            'adjustedBalances'        => array_values($balances),
            'requiresConfirmation'    => true,
        ];
    }

    /**
     * @param array<array{accountId: string, amountCents: int}> $balances
     */
    private function indexByAccountId(array $balances): array
    {
        $indexed = [];

        foreach ($balances as $entry) {
            $indexed[$entry['accountId']] = $entry;
        }

        return $indexed;
    }

    /**
     * Absorbs the discrepancy cascading through the adjustmentPriority accounts.
     * Throws if the discrepancy cannot be fully absorbed.
     */
    private function absorb(array $balances, int $discrepancy, array $priority): array
    {
        $remaining = $discrepancy;

        foreach ($priority as $accountId) {
            if ($remaining === 0) {
                break;
            }

            if (!isset($balances[$accountId])) {
                continue;
            }

            $current  = $balances[$accountId]['amountCents'];
            $adjusted = $current + $remaining;

            if ($adjusted < 0) {
                $balances[$accountId]['amountCents'] = 0;
                $balances[$accountId]['adjustedCents'] = -$current;
                $remaining = $adjusted;
            } else {
                $balances[$accountId]['amountCents'] = $adjusted;
                $balances[$accountId]['adjustedCents'] = $remaining;
                $remaining = 0;
            }
        }

        if ($remaining !== 0) {
            throw new InvalidDataException(
                sprintf(
                    'La discrepancia de %d centavos no pudo absorberse completamente. Sobrante: %d. Revisá los saldos.',
                    $discrepancy,
                    $remaining,
                ),
            );
        }

        return $balances;
    }
}
