<?php

declare(strict_types=1);

namespace App\Context\BillingPolicy\Application\Service;

use App\Context\BillingPolicy\Domain\BillingPolicyMonthSnapshotRepository;
use App\Context\BillingPolicy\Domain\BillingPolicyResolverPort;
use App\Context\BillingPolicy\Domain\ResolvedBillingPolicy;
use App\Context\BillingPolicy\Domain\Service\BillingPolicyResolver;
use App\Context\BillingPolicy\Domain\ValueObject\BillingPolicySnapshot;
use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Gas\Domain\Event\GasPriceWasDefined;
use App\Context\Setup\Domain\Event\OpeningReferenceMonthWasRecorded;
use App\Context\Setup\Domain\OpeningSetupAggregateId;
use DateTimeImmutable;

use function array_key_exists;
use function end;
use function is_numeric;
use function round;

final readonly class BillingPolicyResolverService implements BillingPolicyResolverPort
{
    public function __construct(
        private BillingPolicyMonthSnapshotRepository $snapshotRepository,
        private StoredEventRepository $storedEventRepository,
        private BillingPolicyResolver $billingPolicyResolver,
    ) {}

    public function resolve(string $targetMonth): ResolvedBillingPolicy
    {
        $snapshots = $this->buildSnapshotIndex();

        return $this->billingPolicyResolver->resolve($snapshots, $targetMonth);
    }

    /**
     * @return array<string, BillingPolicySnapshot>
     */
    private function buildSnapshotIndex(): array
    {
        $entities = $this->snapshotRepository->findAllIndexedByTargetMonth();
        $snapshots = [];

        foreach ($entities as $targetMonth => $entity) {
            $snapshots[$targetMonth] = BillingPolicySnapshot::fromEntity($entity);
        }

        if ($snapshots !== []) {
            return $snapshots;
        }

        $opening = $this->latestOpeningReference();
        if ($opening === null) {
            return [];
        }

        $referenceMonth = (string) ($opening->payload()['referenceMonth'] ?? '');
        if ($referenceMonth === '') {
            return [];
        }

        $snapshots[$referenceMonth] = BillingPolicySnapshot::fromOpeningReference(
            $opening->payload(),
            $opening->occurredAt(),
            $this->latestGlobalGasPricePerM3Cents(),
        );

        return $snapshots;
    }

    private function latestOpeningReference(): ?StoredEvent
    {
        $events = $this->storedEventRepository->findByEventTypesAndOccurredBetweenAndAggregateId(
            [OpeningReferenceMonthWasRecorded::eventName()],
            new DateTimeImmutable('1900-01-01'),
            null,
            OpeningSetupAggregateId::VALUE,
        );

        if ($events === []) {
            return null;
        }

        usort(
            $events,
            static function (StoredEvent $a, StoredEvent $b): int {
                $byTime = $a->occurredAt() <=> $b->occurredAt();
                if (0 !== $byTime) {
                    return $byTime;
                }

                return ($a->payload()['referenceMonth'] ?? '') <=> ($b->payload()['referenceMonth'] ?? '');
            },
        );

        return $events[\array_key_last($events)];
    }

    private function latestGlobalGasPricePerM3Cents(): ?int
    {
        $events = $this->storedEventRepository->findByEventType(GasPriceWasDefined::eventName());
        if ($events === []) {
            return null;
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

        return null;
    }
}
