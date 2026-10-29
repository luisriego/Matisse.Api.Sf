<?php

declare(strict_types=1);

namespace App\Tests\Context\BillingPolicy\Application\Service;

use App\Context\BillingPolicy\Application\Service\BillingPolicyResolverService;
use App\Context\BillingPolicy\Domain\BillingPolicyMonthSnapshot;
use App\Context\BillingPolicy\Domain\BillingPolicyMonthSnapshotRepository;
use App\Context\BillingPolicy\Domain\Service\BillingPolicyResolver;
use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Gas\Domain\Event\GasPriceWasDefined;
use App\Context\Setup\Domain\Event\OpeningReferenceMonthWasRecorded;
use App\Context\Setup\Domain\OpeningSetupAggregateId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BillingPolicyResolverServiceTest extends TestCase
{
    private BillingPolicyResolverService $service;

    public function testResolveForMonthBetweenOpeningReferenceAndLaterExplicitSnapshotInheritsOpeningReference(): void
    {
        // Given: OpeningReferenceMonthWasRecorded for 2026-01
        $openingEvent = StoredEvent::create(
            OpeningSetupAggregateId::VALUE,
            OpeningReferenceMonthWasRecorded::eventName(),
            [
                'referenceMonth' => '2026-01',
                'extraFeePerUnitCents' => 25000,
                'reserveFundPerUnitCents' => 9370,
                'expectedSyndicShareTotalCents' => 60000,
            ],
            new DateTimeImmutable('2026-01-01T10:00:00Z'),
        );

        // And: explicit BillingPolicyMonthSnapshot for 2026-06
        $explicitSnapshot = new BillingPolicyMonthSnapshot(
            '2026-06',
            30000,
            10000,
            70000,
            2800,
            new DateTimeImmutable('2026-05-15T12:00:00Z'),
        );

        $snapshotRepo = $this->createMock(BillingPolicyMonthSnapshotRepository::class);
        $snapshotRepo->expects(self::once())
            ->method('findAllIndexedByTargetMonth')
            ->willReturn(['2026-06' => $explicitSnapshot]);

        $eventRepo = $this->createMock(StoredEventRepository::class);
        $eventRepo->expects(self::once())
            ->method('findByEventTypesAndOccurredBetweenAndAggregateId')
            ->willReturn([$openingEvent]);
        $eventRepo->method('findByEventType')
            ->with(GasPriceWasDefined::eventName())
            ->willReturn([]);

        $this->service = new BillingPolicyResolverService(
            $snapshotRepo,
            $eventRepo,
            new BillingPolicyResolver(),
        );

        // When: resolve for 2026-03 (between opening 2026-01 and explicit 2026-06)
        $result = $this->service->resolve('2026-03');

        // Then: inherits from opening reference month, not from explicit snapshot
        self::assertTrue($result->hasPolicy());
        self::assertSame('2026-01', $result->sourceMonth());
        self::assertSame(25000, $result->extraFeePerUnitCents());
        self::assertSame(9370, $result->reserveFundPerUnitCents());
        self::assertSame(60000, $result->syndicShareTotalCents());
    }

    public function testExplicitSnapshotOnSameMonthOverridesOpeningReference(): void
    {
        // Given: OpeningReferenceMonthWasRecorded for 2026-01
        $openingEvent = StoredEvent::create(
            OpeningSetupAggregateId::VALUE,
            OpeningReferenceMonthWasRecorded::eventName(),
            [
                'referenceMonth' => '2026-01',
                'extraFeePerUnitCents' => 25000,
                'reserveFundPerUnitCents' => 9370,
                'expectedSyndicShareTotalCents' => 60000,
            ],
            new DateTimeImmutable('2026-01-01T10:00:00Z'),
        );

        // And: explicit BillingPolicyMonthSnapshot for 2026-01 (same month as opening)
        $explicitSnapshot = new BillingPolicyMonthSnapshot(
            '2026-01',
            28000,
            9500,
            65000,
            2700,
            new DateTimeImmutable('2026-01-15T14:00:00Z'),
        );

        $snapshotRepo = $this->createMock(BillingPolicyMonthSnapshotRepository::class);
        $snapshotRepo->expects(self::once())
            ->method('findAllIndexedByTargetMonth')
            ->willReturn(['2026-01' => $explicitSnapshot]);

        $eventRepo = $this->createMock(StoredEventRepository::class);
        $eventRepo->expects(self::once())
            ->method('findByEventTypesAndOccurredBetweenAndAggregateId')
            ->willReturn([$openingEvent]);

        $this->service = new BillingPolicyResolverService(
            $snapshotRepo,
            $eventRepo,
            new BillingPolicyResolver(),
        );

        // When: resolve for 2026-01 (same month as both opening and explicit)
        $result = $this->service->resolve('2026-01');

        // Then: explicit snapshot prevails over opening reference
        self::assertTrue($result->hasPolicy());
        self::assertTrue($result->explicit());
        self::assertSame('2026-01', $result->sourceMonth());
        self::assertSame(28000, $result->extraFeePerUnitCents());
        self::assertSame(9500, $result->reserveFundPerUnitCents());
        self::assertSame(65000, $result->syndicShareTotalCents());
        self::assertSame(2700, $result->gasPricePerM3Cents());
    }
}
