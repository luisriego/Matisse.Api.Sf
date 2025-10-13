<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use App\Shared\Domain\Event\EventSubscriber;
use InvalidArgumentException;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;

use function sprintf;

final class DomainEventSubscriberExtractor
{
    public static function extract(iterable $subscribers): array
    {
        $extractedSubscribers = [];

        foreach ($subscribers as $subscriber) {
            if (!$subscriber instanceof EventSubscriber) {
                throw new InvalidArgumentException(sprintf(
                    'Subscriber must be an instance of %s, %s given',
                    EventSubscriber::class,
                    $subscriber::class,
                ));
            }

            $subscribedEvents = $subscriber::subscribedTo();

            foreach ($subscribedEvents as $eventClass) {
                if (!isset($extractedSubscribers[$eventClass])) {
                    $extractedSubscribers[$eventClass] = [];
                }

                $extractedSubscribers[$eventClass][] = new HandlerDescriptor(
                    $subscriber,
                    ['method' => '__invoke'], // Ahora pasamos un array de opciones
                );
            }
        }

        return $extractedSubscribers;
    }
}
