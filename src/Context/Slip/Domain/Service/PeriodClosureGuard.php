<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Service;

use App\Context\Slip\Domain\Exception\PeriodAlreadyClosedException;
use App\Context\Slip\Domain\PeriodClosureRepository;

readonly class PeriodClosureGuard
{
    public function __construct(
        private PeriodClosureRepository $periodClosureRepository,
    ) {}

    public function assertNotClosed(int $year, int $month): void
    {
        if ($this->periodClosureRepository->existsForMonth($year, $month)) {
            throw PeriodAlreadyClosedException::forMonth($year, $month);
        }
    }
}
