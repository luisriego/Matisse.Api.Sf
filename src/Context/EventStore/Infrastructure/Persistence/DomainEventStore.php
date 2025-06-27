<?php

declare(strict_types=1);

namespace App\Context\EventStore\Infrastructure\Persistence;

use App\Shared\Application\EventStore;
use App\Shared\Domain\Event\DomainEvent;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DomainEventStore implements EventStore
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function append(DomainEvent $event): void
    {
        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }
}