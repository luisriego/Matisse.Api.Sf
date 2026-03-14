<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure;

use App\Context\Account\Domain\Event\AccountWasEnabled;
use App\Context\User\Domain\Event\UserWasRegistered;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\EventSubscriber;
use App\Shared\Infrastructure\InMemorySymfonyEventBus;
use PHPUnit\Framework\TestCase;

final class InMemorySymfonyEventBusTest extends TestCase
{
    public function test_subscriber_is_invoked_when_event_matches(): void
    {
        $subscriber = new RecordingUserWasRegisteredSubscriber();
        $bus = new InMemorySymfonyEventBus([$subscriber]);

        $event = new UserWasRegistered('user-123', 'Jane', 'jane@example.com', 'token-abc');

        $bus->publish($event);

        self::assertSame(1, $subscriber->callCount);
        self::assertSame($event, $subscriber->lastEvent);
    }

    public function test_subscriber_is_not_invoked_for_different_event_type(): void
    {
        $subscriber = new RecordingUserWasRegisteredSubscriber();
        $bus = new InMemorySymfonyEventBus([$subscriber]);

        $event = new AccountWasEnabled('account-456');

        $bus->publish($event);

        self::assertSame(0, $subscriber->callCount);
        self::assertNull($subscriber->lastEvent);
    }

    public function test_multiple_subscribers_both_receive_matching_event(): void
    {
        $subscriberA = new RecordingUserWasRegisteredSubscriber();
        $subscriberB = new RecordingUserWasRegisteredSubscriber();
        $bus = new InMemorySymfonyEventBus([$subscriberA, $subscriberB]);

        $event = new UserWasRegistered('user-123', 'Jane', 'jane@example.com', 'token-abc');

        $bus->publish($event);

        self::assertSame(1, $subscriberA->callCount);
        self::assertSame($event, $subscriberA->lastEvent);
        self::assertSame(1, $subscriberB->callCount);
        self::assertSame($event, $subscriberB->lastEvent);
    }

    public function test_publishing_multiple_events_invokes_subscriber_per_event(): void
    {
        $subscriber = new RecordingUserWasRegisteredSubscriber();
        $bus = new InMemorySymfonyEventBus([$subscriber]);

        $event1 = new UserWasRegistered('user-1', 'Alice', 'alice@example.com', 'token-1');
        $event2 = new UserWasRegistered('user-2', 'Bob', 'bob@example.com', 'token-2');
        $event3 = new UserWasRegistered('user-3', 'Carol', 'carol@example.com', 'token-3');

        $bus->publish($event1, $event2, $event3);

        self::assertSame(3, $subscriber->callCount);
        self::assertSame($event3, $subscriber->lastEvent);
    }
}

/**
 * Subscriber that records invocations for UserWasRegistered (used to test the bus).
 *
 * @internal
 */
final class RecordingUserWasRegisteredSubscriber implements EventSubscriber
{
    public int $callCount = 0;

    public ?DomainEvent $lastEvent = null;

    /** @param UserWasRegistered $event */
    public function __invoke(DomainEvent $event): void
    {
        $this->callCount++;
        $this->lastEvent = $event;
    }

    public static function subscribedTo(): array
    {
        return [UserWasRegistered::class => '__invoke'];
    }
}
