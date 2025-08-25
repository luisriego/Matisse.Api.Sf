<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\PaySlip;

use App\Context\Slip\Domain\SlipRepository;
use App\Context\Slip\Domain\ValueObject\SlipId;
use App\Shared\Application\CommandHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler]
final readonly class PaySlipCommandHandler implements CommandHandler
{
    public function __construct(
        private SlipRepository $slipRepository,
        #[Autowire(service: 'state_machine.slip')]
        private WorkflowInterface $slipStateMachine,
    ) {}

    public function __invoke(PaySlipCommand $command): void
    {
        $slipId = SlipId::fromString($command->id);
        $slip = $this->slipRepository->findOneByIdOrFail($slipId);

        // The workflow component will check if the transition is possible and apply it.
        // The SlipWorkflowCompletedSubscriber will handle saving the entity and dispatching events.
        $this->slipStateMachine->apply($slip, 'pay_from_submitted');
    }
}
