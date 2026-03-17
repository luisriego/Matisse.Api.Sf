<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use App\Context\EventStore\Domain\StoredEvent;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\Event\EventBus;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::preFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class DomainEventCollectorSubscriber
{
    private array $collectedEvents = [];

    public function __construct(private EventBus $eventBus) {}

    public function preFlush(PreFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        // 1. Check Identity Map (modified or loaded entities but tracking might have caught events)
        foreach ($uow->getIdentityMap() as $entitiesByClass) {
            foreach ($entitiesByClass as $entity) {
                $this->collectEventsFromEntity($entity, $em);
            }
        }

        // 2. Check scheduled insertions
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->collectEventsFromEntity($entity, $em);
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $eventsToDispatch = $this->collectedEvents;

        // Limpiamos el array ANTES de despachar para evitar ciclos infinitos si un dispatch causa otro flush
        $this->collectedEvents = [];

        foreach ($eventsToDispatch as $domainEvent) {
            $this->eventBus->publish($domainEvent);
        }
    }

    private function collectEventsFromEntity(object $entity, $em): void
    {
        if (!$entity instanceof AggregateRoot) {
            return;
        }

        foreach ($entity->pullDomainEvents() as $domainEvent) {
            $storedEvent = StoredEvent::create(
                $domainEvent->aggregateId(),
                $domainEvent::eventName(),
                $domainEvent->toPrimitives(),
                $domainEvent->occurredOn(),
            );

            // Persistir en la misma transaccion de UnitOfWork (Outbox atomico)
            $em->persist($storedEvent);

            // Retener el evento de dominio original para ser despachado a los listeners post-transaccion
            $this->collectedEvents[] = $domainEvent;
        }
    }
}
