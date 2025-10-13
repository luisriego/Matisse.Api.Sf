<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\WorkflowInterface;

use function sprintf;

final readonly class SlipWorkflowService
{
    public function __construct(
        #[Autowire(service: 'slip.state_machine')] // usa exactamente el id sugerido por el error
        private WorkflowInterface $slipWorkflow,
    ) {}

    public function send(object $slip): void
    {
        $this->apply($slip, 'send');
    }

    public function pay(object $slip): void
    {
        if ($this->slipWorkflow->can($slip, 'pay_from_submitted')) {
            $this->apply($slip, 'pay_from_submitted');
        }
    }

    public function expire(object $slip): void
    {
        $this->apply($slip, 'expire');
    }

    public function cancel(object $slip): void
    {
        foreach (['void_from_pending', 'void_from_submitted', 'void_from_overdue'] as $t) {
            if ($this->slipWorkflow->can($slip, $t)) {
                $this->apply($slip, $t);

                return;
            }
        }

        throw new LogicException('Cancellation not allowed from current state.');
    }

    private function apply(object $slip, string $transition): void
    {
        if (!$this->slipWorkflow->can($slip, $transition)) {
            throw new LogicException(sprintf('Transition "%s" not allowed.', $transition));
        }
        $this->slipWorkflow->apply($slip, $transition);
    }
}
