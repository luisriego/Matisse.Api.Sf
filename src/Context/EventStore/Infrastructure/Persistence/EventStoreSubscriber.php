<?php

declare(strict_types=1);

namespace App\Context\EventStore\Infrastructure\Persistence;

use App\Shared\Domain\Event\DomainEvent;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Types;

use function json_encode;
use function md5;

readonly class EventStoreSubscriber
{
    public function __construct(
        private Connection $connection,
    ) {}

    public function __invoke(DomainEvent $event): void
    {
        try {
            $contentHash = $this->generateEventHash($event);

            $this->connection->insert(
                'event_store',
                [
                    'id' => $event->eventId(),
                    'aggregate_id' => $event->aggregateId(),
                    'event_name' => $event->eventName(),
                    'body' => json_encode($event->toPrimitives()),
                    'occurred_on' => $event->occurredOn()->format('Y-m-d H:i:s'),
                    'content_hash' => $contentHash,
                ],
                [
                    'body' => Types::JSON,
                ],
            );
        } catch (UniqueConstraintViolationException) {
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function generateEventHash(DomainEvent $event): string
    {
        // Crear un hash único basado en el contenido del evento
        return md5(
            $event->aggregateId()
            . $event->eventName()
            . json_encode($event->toPrimitives()),
        );
    }
}
