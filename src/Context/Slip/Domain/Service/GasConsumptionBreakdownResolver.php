<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Service;

use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;

use function array_key_exists;
use function end;
use function is_numeric;
use function round;
use function usort;

/**
 * Resuelve el desglose de gas desde las lecturas del medidor (gas.reading.was.recorded)
 * y el precio vigente (gas.price.was.defined) para fines de auditoría en el explain.
 */
readonly class GasConsumptionBreakdownResolver
{
    public function __construct(
        private StoredEventRepository $storedEventRepository,
    ) {}

    /**
     * @return array{
     *     pricePerM3Cents: int,
     *     byUnit: array<string, array{
     *         previousReading: float|null,
     *         previousMonth: string,
     *         currentReading: float|null,
     *         currentMonth: string,
     *         consumptionM3: float,
     *         gasCents: int,
     *     }>,
     *     gasTotalCents: int
     * }
     */
    public function breakdownForMonth(
        int $year,
        int $month,
        array $residentUnitIds,
        ?int $pricePerM3CentsOverride = null,
    ): array {
        $pricePerM3Cents = $pricePerM3CentsOverride ?? $this->resolveLatestGasPrice();

        $previousMonth = $month - 1;
        $previousYear = $year;
        if ($previousMonth < 1) {
            $previousMonth = 12;
            $previousYear--;
        }

        $readingsByUnit = $this->fetchReadingsGroupedByUnit();

        $byUnit = [];
        $gasTotalCents = 0;

        foreach ($residentUnitIds as $unitId) {
            $currentReading = $this->findReading($readingsByUnit, $unitId, $year, $month);
            $previousReading = $this->findReading($readingsByUnit, $unitId, $previousYear, $previousMonth);

            $consumptionM3 = 0.0;
            if ($currentReading !== null && $previousReading !== null && $currentReading >= $previousReading) {
                $consumptionM3 = round($currentReading - $previousReading, 3);
            }

            $gasCents = (int) round($consumptionM3 * $pricePerM3Cents);

            $byUnit[$unitId] = [
                'previousReading' => $previousReading,
                'previousMonth' => sprintf('%04d-%02d', $previousYear, $previousMonth),
                'currentReading' => $currentReading,
                'currentMonth' => sprintf('%04d-%02d', $year, $month),
                'consumptionM3' => $consumptionM3,
                'gasCents' => $gasCents,
            ];

            $gasTotalCents += $gasCents;
        }

        return [
            'pricePerM3Cents' => $pricePerM3Cents,
            'byUnit' => $byUnit,
            'gasTotalCents' => $gasTotalCents,
        ];
    }

    /**
     * @return array<string, list<array{year: int, month: int, reading: float, occurredAt: \DateTimeImmutable}>>
     */
    private function fetchReadingsGroupedByUnit(): array
    {
        $events = $this->storedEventRepository->findByEventType('gas.reading.was.recorded');
        $grouped = [];

        foreach ($events as $event) {
            $payload = $event->payload();
            $unitId = $payload['residentUnitId'] ?? null;
            if ($unitId === null) {
                continue;
            }

            $grouped[$unitId][] = [
                'year' => (int) $payload['year'],
                'month' => (int) $payload['month'],
                'reading' => (float) $payload['reading'],
                'occurredAt' => $event->occurredAt(),
            ];
        }

        return $grouped;
    }

    /**
     * Encuentra la última lectura válida para una unidad en un mes/año dado.
     * Si hay múltiples lecturas para el mismo mes, toma la más reciente (por occurredAt).
     */
    private function findReading(array $readingsByUnit, string $unitId, int $year, int $month): ?float
    {
        $readings = $readingsByUnit[$unitId] ?? [];
        $candidates = [];

        foreach ($readings as $r) {
            if ($r['year'] === $year && $r['month'] === $month) {
                $candidates[] = $r;
            }
        }

        if (empty($candidates)) {
            return null;
        }

        usort($candidates, static fn($a, $b) => $b['occurredAt'] <=> $a['occurredAt']);

        return $candidates[0]['reading'];
    }

    private function resolveLatestGasPrice(): int
    {
        $events = $this->storedEventRepository->findByEventType('gas.price.was.defined');

        if (empty($events)) {
            return 0;
        }

        /** @var StoredEvent $latest */
        $latest = end($events);
        $payload = $latest->payload();

        if (array_key_exists('pricePerM3InCents', $payload) && is_numeric($payload['pricePerM3InCents'])) {
            return (int) $payload['pricePerM3InCents'];
        }

        if (array_key_exists('pricePerM3', $payload) && is_numeric($payload['pricePerM3'])) {
            return (int) round((float) $payload['pricePerM3'] * 100);
        }

        return 0;
    }
}
