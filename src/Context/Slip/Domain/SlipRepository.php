<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain;

use App\Context\Slip\Domain\ValueObject\SlipId;
use App\Shared\Domain\ValueObject\DateRange;

interface SlipRepository
{
    public function flush(): void;

    public function save(Slip $Slip, bool $flush = true): void;

    public function findOneByIdOrFail(SlipId $id): Slip;

    public function deleteByDateRange(DateRange $dateRange): void;

    public function existsForDueDateMonth(int $year, int $month): bool;

    /**
     * Finds slips by a set of ids.
     *
     * @param string[] $ids
     *
     * @return Slip[]
     */
    public function findManyByIds(array $ids): array;

    /**
     * Returns all non-cancelled slips whose due date falls within the given month/year.
     *
     * @return Slip[]
     */
    public function findByMonthYear(int $year, int $month): array;

    /**
     * Sum of amounts (in cents) for all non-cancelled slips whose dueDate falls in the given year/month.
     * Used to validate bank CREDIT settlements against the expected total.
     */
    public function sumAmountByDueDateMonth(int $year, int $month): int;
}
