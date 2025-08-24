<?php

declare(strict_types=1);

// Namespace corregido para asegurar que coincide con la ruta del fichero

namespace App\Context\Slip\Application\UseCase\SendBulkSlips;

use App\Context\Slip\Domain\SlipRepository;
use App\Shared\Application\CommandHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler]
final readonly class SendBulkSlipsCommandHandler implements CommandHandler
{
    public function __construct(
        private SlipRepository $slipRepository,
        #[Autowire(service: 'state_machine.slip')]
        private WorkflowInterface $slipStateMachine,
    ) {}

    public function __invoke(SendBulkSlipsCommand $command): void
    {
        $slips = $this->slipRepository->findManyByIds($command->slipIds);

        foreach ($slips as $slip) {
            if ($this->slipStateMachine->can($slip, 'send')) {
                $this->slipStateMachine->apply($slip, 'send');
            }
        }
    }
}
