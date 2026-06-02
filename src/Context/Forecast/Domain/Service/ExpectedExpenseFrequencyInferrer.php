<?php

declare(strict_types=1);

namespace App\Context\Forecast\Domain\Service;

use function array_map;
use function array_unique;
use function array_values;
use function count;
use function range;
use function sort;

final class ExpectedExpenseFrequencyInferrer
{
    /**
     * @param list<int>|null $monthsOfYear
     *
     * @return array{frequency: string, monthsOfYear: list<int>}
     */
    public function infer(?array $monthsOfYear): array
    {
        if ($monthsOfYear === null || $monthsOfYear === []) {
            return ['frequency' => 'monthly', 'monthsOfYear' => range(1, 12)];
        }

        $months = array_values(array_unique(array_map('intval', $monthsOfYear)));
        sort($months);

        if ($months === range(1, 12)) {
            return ['frequency' => 'monthly', 'monthsOfYear' => $months];
        }

        if (count($months) === 1) {
            return ['frequency' => 'annual', 'monthsOfYear' => $months];
        }

        if ($months === [6, 12]) {
            return ['frequency' => 'semi_annual', 'monthsOfYear' => $months];
        }

        return ['frequency' => 'custom', 'monthsOfYear' => $months];
    }
}
