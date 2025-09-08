<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Application\Service;

use App\Context\Slip\Application\Service\SlipWorkflowService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\WorkflowInterface;

class SlipWorkflowServiceTest extends TestCase
{
    private WorkflowInterface $slipWorkflow;
    private SlipWorkflowService $service;

    protected function setUp(): void
    {
        $this->slipWorkflow = $this->createMock(WorkflowInterface::class);
        $this->service = new SlipWorkflowService($this->slipWorkflow);
    }

    public function test_send_transition_is_applied_when_allowed(): void
    {
        $slip = new \stdClass(); // Dummy slip object

        $this->slipWorkflow->expects($this->once()) // Only called in apply()
            ->method('can')
            ->with($slip, 'send')
            ->willReturn(true);

        $this->slipWorkflow->expects($this->once())
            ->method('apply')
            ->with($slip, 'send');

        $this->service->send($slip);
    }

    public function test_send_transition_throws_exception_when_not_allowed(): void
    {
        $slip = new \stdClass(); // Dummy slip object

        $this->slipWorkflow->expects($this->once())
            ->method('can')
            ->with($slip, 'send')
            ->willReturn(false);

        $this->slipWorkflow->expects($this->never())
            ->method('apply');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Transition "send" not allowed.');

        $this->service->send($slip);
    }

    public function test_pay_transition_is_applied_when_allowed(): void
    {
        $slip = new \stdClass(); // Dummy slip object

        $this->slipWorkflow->expects($this->exactly(2)) // Called in pay() and then in apply()
            ->method('can')
            ->with($slip, 'pay_from_submitted')
            ->willReturn(true);

        $this->slipWorkflow->expects($this->once())
            ->method('apply')
            ->with($slip, 'pay_from_submitted');

        $this->service->pay($slip);
    }

    public function test_pay_transition_does_nothing_when_not_allowed(): void
    {
        $slip = new \stdClass(); // Dummy slip object

        $this->slipWorkflow->expects($this->once())
            ->method('can')
            ->with($slip, 'pay_from_submitted')
            ->willReturn(false);

        $this->slipWorkflow->expects($this->never())
            ->method('apply');

        // No exception is expected for 'pay' when not allowed, it just does nothing
        $this->service->pay($slip);
    }

    public function test_expire_transition_is_applied_when_allowed(): void
    {
        $slip = new \stdClass(); // Dummy slip object

        $this->slipWorkflow->expects($this->once()) // Only called in apply()
            ->method('can')
            ->with($slip, 'expire')
            ->willReturn(true);

        $this->slipWorkflow->expects($this->once())
            ->method('apply')
            ->with($slip, 'expire');

        $this->service->expire($slip);
    }

    public function test_expire_transition_throws_exception_when_not_allowed(): void
    {
        $slip = new \stdClass(); // Dummy slip object

        $this->slipWorkflow->expects($this->once())
            ->method('can')
            ->with($slip, 'expire')
            ->willReturn(false);

        $this->slipWorkflow->expects($this->never())
            ->method('apply');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Transition "expire" not allowed.');

        $this->service->expire($slip);
    }

    public function test_cancel_transition_is_applied_when_allowed(): void
    {
        $slip = new \stdClass(); // Dummy slip object

        // Simulate that 'void_from_submitted' is the allowed transition
        $this->slipWorkflow->expects($this->exactly(3)) // Called for 'void_from_pending', then 'void_from_submitted', and then in apply()
            ->method('can')
            ->willReturnMap([
                [$slip, 'void_from_pending', false],
                [$slip, 'void_from_submitted', true],
            ]);

        $this->slipWorkflow->expects($this->once())
            ->method('apply')
            ->with($slip, 'void_from_submitted');

        $this->service->cancel($slip);
    }

    public function test_cancel_transition_throws_exception_when_not_allowed_from_any_state(): void
    {
        $slip = new \stdClass(); // Dummy slip object

        // Simulate that no cancel transitions are allowed
        $this->slipWorkflow->expects($this->exactly(3)) // Called for all three void transitions
            ->method('can')
            ->willReturn(false);

        $this->slipWorkflow->expects($this->never())
            ->method('apply');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cancellation not allowed from current state.');

        $this->service->cancel($slip);
    }
}
