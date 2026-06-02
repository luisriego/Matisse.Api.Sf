<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Service;

use App\Context\Account\Domain\Account;
use App\Context\Account\Domain\AccountRepository;
use App\Context\Income\Domain\IncomeType;
use App\Context\Income\Domain\IncomeTypeRepository;
use InvalidArgumentException;

use function mb_strtolower;
use function preg_match;
use function sprintf;
use function trim;

/**
 * Resolves bookkeeping accounts and income types for each settlement component
 * by matching names from the database — no hardcoded UUIDs in env vars.
 *
 * Components: base, syndic, extra, reserve, gas.
 */
final class SettlementAccountResolver
{
    private const COMPONENTS = ['base', 'syndic', 'extra', 'reserve', 'gas'];

    private const ACCOUNT_PATTERNS = [
        'base'    => '/conta\s+principal|cuenta\s+principal|\bprincipal\b|main\s+account|cc\s+banco/i',
        'syndic'  => '/conta\s+principal|cuenta\s+principal|\bprincipal\b|main\s+account|cc\s+banco/i',
        'extra'   => '/fundo\s+de\s+obra|\bobra\b/i',
        'reserve' => '/fundo\s+de\s+reserva|\breserva\b/i',
        'gas'     => '/\b(gas|gás|gnv)\b/i',
    ];

    private const INCOME_TYPE_PATTERNS = [
        'base'    => '/taxa\s+condominial|condominial/i',
        'syndic'  => '/taxa\s+condominial|condominial/i',
        'extra'   => '/cota\s+extra|taxa\s+extra/i',
        'reserve' => '/taxa\s+condominial|condominial/i',
        'gas'     => '/taxa\s+condominial|condominial/i',
    ];

    /** @var array<string, array{accountId: string, incomeTypeId: string}>|null */
    private ?array $resolvedMapping = null;

    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly IncomeTypeRepository $incomeTypeRepository,
        private readonly bool $enabled = true,
    ) {}

    public function shouldSplit(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $mapping = $this->resolve();

        foreach (self::COMPONENTS as $key) {
            if (!isset($mapping[$key])) {
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
        $mapping = $this->resolve();
        $row = $mapping[$componentKey] ?? null;

        if ($row === null) {
            throw new InvalidArgumentException(sprintf(
                'Cannot resolve settlement component "%s": no matching account or income type found in the database.',
                $componentKey,
            ));
        }

        return $row;
    }

    /**
     * Lazily resolves the full mapping once per request.
     *
     * @return array<string, array{accountId: string, incomeTypeId: string}>
     */
    private function resolve(): array
    {
        if ($this->resolvedMapping !== null) {
            return $this->resolvedMapping;
        }

        $accounts    = $this->accountRepository->findAllActive();
        $incomeTypes = $this->incomeTypeRepository->findAll();

        $mapping = [];

        foreach (self::COMPONENTS as $component) {
            $account    = $this->matchAccount($accounts, $component);
            $incomeType = $this->matchIncomeType($incomeTypes, $component);

            if ($account !== null && $incomeType !== null) {
                $mapping[$component] = [
                    'accountId'    => $account->id(),
                    'incomeTypeId' => $incomeType->id(),
                ];
            }
        }

        $this->resolvedMapping = $mapping;

        return $mapping;
    }

    /**
     * @param Account[] $accounts
     */
    private function matchAccount(array $accounts, string $component): ?Account
    {
        $pattern = self::ACCOUNT_PATTERNS[$component] ?? null;

        if ($pattern === null) {
            return null;
        }

        foreach ($accounts as $account) {
            $name = mb_strtolower(trim((string) $account->name()));

            if (preg_match($pattern, $name) === 1) {
                return $account;
            }
        }

        return null;
    }

    /**
     * @param IncomeType[] $incomeTypes
     */
    private function matchIncomeType(array $incomeTypes, string $component): ?IncomeType
    {
        $pattern = self::INCOME_TYPE_PATTERNS[$component] ?? null;

        if ($pattern === null) {
            return null;
        }

        foreach ($incomeTypes as $type) {
            $name = mb_strtolower(trim((string) $type->name()));

            if (preg_match($pattern, $name) === 1) {
                return $type;
            }
        }

        return null;
    }
}
