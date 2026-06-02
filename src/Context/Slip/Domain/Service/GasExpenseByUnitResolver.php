<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Service;

use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\ExpenseTypeRepository;
use DateTimeImmutable;

use function is_array;
use function sprintf;

/**
 * Suma neta por unidad desde eventos expense.entered / expense.compensated
 * con tipo SP3GA, en un mes calendario concreto.
 */
readonly class GasExpenseByUnitResolver
{
    public function __construct(
        private StoredEventRepository $storedEventRepository,
        private ExpenseTypeRepository $expenseTypeRepository,
    ) {}

    /**
     * @return array<string, int> residentUnitId => centavos (puede ser negativo con compensaciones)
     */
    public function sumByResidentUnitForCalendarMonth(int $year, int $month): array
    {
        $gasExpenses = [];

        $startDate = new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $endDate = $startDate->modify('last day of this month 23:59:59');

        $events = $this->storedEventRepository->findByEventTypesAndOccurredBetween(
            ['expense.entered', 'expense.compensated'],
            $startDate,
            $endDate,
        );

        $gasTypeId = $this->resolveGasExpenseTypeId();

        foreach ($events as $event) {
            $body = $this->normalizedEventPayload($event->payload());

            if (isset($body['type']) && $body['type'] === $gasTypeId) {
                $residentUnitId = $body['residentUnitId'] ?? null;
                $amount = (int) ($body['amount'] ?? 0);

                if ($residentUnitId) {
                    if (!isset($gasExpenses[$residentUnitId])) {
                        $gasExpenses[$residentUnitId] = 0;
                    }
                    $gasExpenses[$residentUnitId] += $amount;
                }
            }
        }

        return $gasExpenses;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizedEventPayload(array $payload): array
    {
        if (isset($payload['body']) && is_array($payload['body'])) {
            return $payload['body'];
        }

        return $payload;
    }

    private function resolveGasExpenseTypeId(): string
    {
        /** @var ExpenseType $type */
        $type = $this->expenseTypeRepository->findOneByCodeOrFail('SP3GA');

        return $type->id();
    }
}
