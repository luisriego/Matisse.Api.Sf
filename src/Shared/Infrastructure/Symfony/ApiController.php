<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony;

use App\Shared\Application\Command;
use App\Shared\Application\Query;
use Symfony\Component\Messenger\Exception\HandlerFailedException; // 1. Importar
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

abstract class ApiController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus,
    ) {}

    abstract protected function exceptions(): array;

    protected function dispatch(Command $command): void
    {
        $this->commandBus->dispatch($command);
    }

    protected function ask(Query $query): mixed
    {
        try { // 2. Envolver la llamada en un try/catch
            return $this->queryBus->dispatch($query)->last(HandledStamp::class)->getResult();
        } catch (HandlerFailedException $e) {
            // 3. Si falla, desenvolver y volver a lanzar la excepción original
            // Esto asegura que los listeners y el mapeo del controlador reciban la excepción de negocio
            throw $e->getPrevious() ?? $e;
        }
    }
}
