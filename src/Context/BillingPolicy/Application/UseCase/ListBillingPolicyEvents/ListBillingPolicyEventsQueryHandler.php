<?php

declare(strict_types=1);

namespace App\Context\BillingPolicy\Application\UseCase\ListBillingPolicyEvents;

use App\Context\BillingPolicy\Domain\Event\MonthlyBillingParametersWereRecorded;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Shared\Application\QueryHandler;

final readonly class ListBillingPolicyEventsQueryHandler implements QueryHandler
{
    public function __construct(private StoredEventRepository $storedEventRepository) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(ListBillingPolicyEventsQuery $query): array
    {
        $events = $this->storedEventRepository->findByEventType(
            MonthlyBillingParametersWereRecorded::eventName(),
        );

        if ($events === []) {
            return [];
        }

        usort(
            $events,
            static fn ($a, $b) => $b->occurredAt() <=> $a->occurredAt(),
        );

        $limit = max(1, min(200, $query->limit()));
        $events = \array_slice($events, 0, $limit);

        $out = [];
        foreach ($events as $event) {
            $payload = $event->payload();
            $out[] = [
                'id' => $event->id(),
                'type' => 'monthly_billing_parameters_recorded',
                'targetMonth' => $payload['targetMonth'] ?? '',
                'payload' => [
                    'extraFeePerUnitCents' => (int) ($payload['extraFeePerUnitCents'] ?? 0),
                    'reserveFundPerUnitCents' => (int) ($payload['reserveFundPerUnitCents'] ?? 0),
                    'syndicShareTotalCents' => (int) ($payload['syndicShareTotalCents'] ?? 0),
                    'gasPricePerM3Cents' => array_key_exists('gasPricePerM3Cents', $payload)
                        ? ($payload['gasPricePerM3Cents'] !== null ? (int) $payload['gasPricePerM3Cents'] : null)
                        : null,
                ],
                'recordedAt' => $event->occurredAt()->format(DATE_ATOM),
                'recordedByUserId' => $payload['recordedByUserId'] ?? null,
            ];
        }

        return $out;
    }
}
