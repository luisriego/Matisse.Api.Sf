<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Workflow;

use App\Context\Slip\Domain\SlipRepository;
use App\Context\Slip\Domain\ValueObject\SlipId;
use App\Shared\Domain\Event\DomainEventDispatcherInterface;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

use function array_keys;
use function method_exists;

/**
 * Suscriptor de eventos del Workflow "slip".
 * Importante: los eventos de dominio se generan en el agregado. Aquí solo
 * se orquesta la llamada al dominio y el despacho de los eventos.
 */
final readonly class SlipWorkflowCompletedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SlipRepository $slipRepository,
        private DomainEventDispatcherInterface $eventDispatcher,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // pending -> submitted
            'workflow.slip.completed.send' => 'onSubmitted',

            // submitted -> paid
            'workflow.slip.completed.pay_from_submitted' => 'onPaid',

            // pending|submitted -> overdue
            'workflow.slip.completed.expire' => 'onExpired',

            // -> cancelled
            'workflow.slip.completed.void_from_pending'   => 'onCancelled',
            'workflow.slip.completed.void_from_submitted' => 'onCancelled',
            'workflow.slip.completed.void_from_overdue'   => 'onCancelled',
        ];
    }

    /**
     * pending -> submitted.
     */
    public function onSubmitted(CompletedEvent $event): void
    {
        $entity = $event->getSubject(); // Doctrine Entity
        $slipId = $this->extractId($entity);

        $slip = $this->slipRepository->findOneByIdOrFail(SlipId::fromString($slipId));

        // Si añades un método de dominio específico (recomendado) como markAsSubmitted(),
        // llámalo para que el agregado registre el evento SlipWasSubmitted.
        if (method_exists($slip, 'markAsSubmitted')) {
            $slip->markAsSubmitted();
        }

        $this->slipRepository->save($slip, true);
        $this->eventDispatcher->dispatchAll($slip->pullDomainEvents());

        // Si tu entidad infra mantiene un campo "status" además de "currentPlace", sincronízalo.
        $this->syncInfraStatusFromMarking($entity, $event);
    }

    /**
     * submitted -> paid.
     */
    public function onPaid(CompletedEvent $event): void
    {
        $entity = $event->getSubject();
        $slipId = $this->extractId($entity);

        $slip = $this->slipRepository->findOneByIdOrFail(SlipId::fromString($slipId));
        $slip->markAsPaid(); // El agregado define paidAt y registra eventos (si aplica)

        $this->slipRepository->save($slip);
        $this->eventDispatcher->dispatchAll($slip->pullDomainEvents());

        $this->syncInfraStatusFromMarking($entity, $event);
    }

    /**
     * pending|submitted -> overdue.
     */
    public function onExpired(CompletedEvent $event): void
    {
        $entity = $event->getSubject();
        $slipId = $this->extractId($entity);

        $slip = $this->slipRepository->findOneByIdOrFail(SlipId::fromString($slipId));
        $slip->markAsOverdue();

        $this->slipRepository->save($slip);
        $this->eventDispatcher->dispatchAll($slip->pullDomainEvents());

        $this->syncInfraStatusFromMarking($entity, $event);
    }

    /**
     * -> cancelled.
     */
    public function onCancelled(CompletedEvent $event): void
    {
        $entity = $event->getSubject();
        $slipId = $this->extractId($entity);

        $slip = $this->slipRepository->findOneByIdOrFail(SlipId::fromString($slipId));
        $slip->markAsCancelled();

        $this->slipRepository->save($slip);
        $this->eventDispatcher->dispatchAll($slip->pullDomainEvents());

        $this->syncInfraStatusFromMarking($entity, $event);
    }

    /**
     * Extrae el id del Slip desde la entidad de infraestructura.
     */
    private function extractId(object $entity): string
    {
        if (method_exists($entity, 'id')) {
            return $entity->id();
        }

        if (method_exists($entity, 'getId')) {
            return $entity->getId();
        }

        throw new RuntimeException('Cannot extract Slip id from workflow subject.');
    }

    /**
     * Sincroniza, si existe, un campo "status" de la entidad infra con el place activo del Workflow.
     * El Workflow ya habrá cambiado "currentPlace" por marking_store.method.
     */
    private function syncInfraStatusFromMarking(object $entity, CompletedEvent $event): void
    {
        $places = array_keys($event->getMarking()->getPlaces());
        $currentPlace = $places[0] ?? null;

        if ($currentPlace === null) {
            return;
        }

        if (method_exists($entity, 'setStatus')) {
            $entity->setStatus($currentPlace);
        }
    }
}
