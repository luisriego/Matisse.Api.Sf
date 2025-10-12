<?php

declare(strict_types=1);

namespace App\Context\EventStore\Infrastructure\Persistence\Doctrine;

use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Shared\Application\EventStore; // Importar la interfaz EventStore
use App\Shared\Domain\Event\DomainEvent; // Importar DomainEvent
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

// Clase renombrada para reflejar su doble responsabilidad
class DoctrineStoreEventRepository extends ServiceEntityRepository implements StoredEventRepository, EventStore
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoredEvent::class);
    }

    // Implementación de StoredEventRepository::save
    public function save(StoredEvent $event, bool $flush = true): void
    {
        $this->getEntityManager()->persist($event);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // Implementación modificada de StoredEventRepository::findByAggregateId
    public function findByAggregateId(string $aggregateId): array
    {
        return $this->findBy(['aggregateId' => $aggregateId], ['occurredAt' => 'ASC']);
    }

    // Implementación modificada de StoredEventRepository::findByEventNamesAndOccurredBetween
    public function findByEventNamesAndOccurredBetween(
        array $eventNames,
        DateTimeImmutable $startDate,
        ?DateTimeImmutable $endDate = null,
    ): array {
        $qb = $this->createQueryBuilder('e');

        $qb->where($qb->expr()->in('e.name', ':names'))
           ->andWhere('e.occurredAt >= :start')
           ->setParameter('names', $eventNames)
           ->setParameter('start', $startDate)
           ->orderBy('e.occurredAt', 'ASC');

        if ($endDate !== null) {
            $qb->andWhere('e.occurredAt <= :end')
               ->setParameter('end', $endDate);
        }

        return $qb->getQuery()->getResult();
    }

    // Implementación de EventStore::append (movida desde DoctrineEventStore)
    public function append(DomainEvent $event): void
    {
        $storedEvent = StoredEvent::create(
            $event->aggregateId(),
            $event::eventName(),
            $event->toPrimitives(),
        );

        $this->getEntityManager()->persist($storedEvent);
    }
}
