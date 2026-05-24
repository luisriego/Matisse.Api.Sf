<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\ImportConsolidatedSlips;

use App\Shared\Application\Command;

final readonly class ImportConsolidatedSlipsCommand implements Command
{
    /**
     * @param list<array{residentUnitId: string, amountCents: int, components?: array<string, int>}> $slips
     */
    public function __construct(
        private int $year,
        private int $month,
        private array $slips,
    ) {}

    public function year(): int
    {
        return $this->year;
    }

    public function month(): int
    {
        return $this->month;
    }

    /**
     * @return list<array{residentUnitId: string, amountCents: int, components?: array<string, int>}>
     */
    public function slips(): array
    {
        return $this->slips;
    }
}
