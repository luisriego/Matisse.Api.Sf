<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\Subscriber;

use App\Context\Slip\Application\Message\SendSlipNotification;
use App\Context\Slip\Domain\Event\SlipWasSubmitted;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\EventSubscriber;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class EnqueueSlipNotificationOnSlipWasSubmitted implements EventSubscriber
{
    public function __construct(private MessageBusInterface $bus) {}

    public function __invoke(DomainEvent $event): void
    {
        $this->bus->dispatch(new SendSlipNotification(
            $event->aggregateId(),
            $event->residentUnitId(),
            $event->amount(),
            $event->dueDate(),
        ));
    }

    public static function subscribedTo(): array
    {
        return [SlipWasSubmitted::class => '__invoke'];
    }
}
