<?php

declare(strict_types=1);

namespace App\Context\EventStore\Infrastructure\Persistence\Doctrine;

use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineStoreEventRepository extends ServiceEntityRepository implements StoredEventRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoredEvent::class);
    }

    public function save(StoredEvent $event, bool $flush = true): void
    {
        $this->getEntityManager()->persist($event);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByAggregateId(string $aggregateId): StoredEvent
    {
        if (null === $storedEvent = $this->findOneBy(['aggregateId' => $aggregateId])) {
            throw ResourceNotFoundException::createFromClassAndId(StoredEvent::class, $aggregateId);
        }

        return $storedEvent;
    }
}
