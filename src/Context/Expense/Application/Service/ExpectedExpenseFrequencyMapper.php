<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\Service;

use InvalidArgumentException;

use function array_map;
use function array_unique;
use function array_values;
use function range;
use function sort;
use function sprintf;

final class ExpectedExpenseFrequencyMapper
{
    /**
     * @return list<int>
     */
    public function monthsOfYear(string $frequency, ?array $customMonths): array
    {
        $normalized = match ($frequency) {
            'monthly' => range(1, 12),
            'annual' => $customMonths ?? [1],
            'semi_annual' => $customMonths ?? [6, 12],
            'custom' => $customMonths,
            default => throw new InvalidArgumentException(
                sprintf('frequency must be monthly, annual, semi_annual or custom; got "%s".', $frequency),
            ),
        };

        if ($normalized === null || $normalized === []) {
            throw new InvalidArgumentException('monthsOfYear is required when frequency is custom.');
        }

        $months = array_values(array_unique(array_map('intval', $normalized)));
        sort($months);

        foreach ($months as $month) {
            if ($month < 1 || $month > 12) {
                throw new InvalidArgumentException('monthsOfYear values must be between 1 and 12.');
            }
        }

        return $months;
    }
}
