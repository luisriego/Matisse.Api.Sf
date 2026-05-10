<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Service;

/**
 * Maps slip breakdown components to target bookkeeping accounts and income types
 * when splitting a boleto settlement bank credit into several {@see EnterIncome} records.
 *
 * Configured via environment variables (see BankStatement.yaml).
 */
final readonly class SettlementIncomeSplitMap
{
    private const COMPONENTS = ['base', 'syndic', 'extra', 'reserve', 'gas'];

    /**
     * @param array<string, array{accountId: string, incomeTypeId: string}> $mapping
     */
    public function __construct(
        private bool $enabled,
        private array $mapping,
    ) {}

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function shouldSplit(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        foreach (self::COMPONENTS as $key) {
            $row = $this->mapping[$key] ?? null;
            if (!is_array($row)) {
                return false;
            }
            $accountId = trim((string) ($row['accountId'] ?? ''));
            $incomeTypeId = trim((string) ($row['incomeTypeId'] ?? ''));
            if ($accountId === '' || $incomeTypeId === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{accountId: string, incomeTypeId: string}
     */
    public function accountAndTypeFor(string $componentKey): array
    {
        $row = $this->mapping[$componentKey] ?? null;
        if (!is_array($row)) {
            throw new \InvalidArgumentException(sprintf('Unknown settlement component "%s".', $componentKey));
        }

        return [
            'accountId' => trim((string) $row['accountId']),
            'incomeTypeId' => trim((string) $row['incomeTypeId']),
        ];
    }
}
