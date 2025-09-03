<?php

declare(strict_types=1);

namespace App\Context\EventStore\Infrastructure\Persistence\Doctrine;

use App\Context\EventStore\Domain\StoredEvent;
use App\Shared\Application\EventStore;
use App\Shared\Domain\Event\DomainEvent;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineEventStore implements EventStore
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function append(DomainEvent $event): void
    {
        $storedEvent = StoredEvent::create(
            $event->aggregateId(),
            $event::eventName(),
            $event->toPrimitives(),
        );

        $this->entityManager->persist($storedEvent);
        $this->entityManager->flush();
    }

    public function getEventsFrom(string $aggregateId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(StoredEvent::class, 'e')
            ->where('e.aggregateId = :aggregateId')
            ->setParameter('aggregateId', $aggregateId)
            ->orderBy('e.occurredAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
