<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Application\Service;

use App\Context\Slip\Application\Service\SlipWorkflowService;
use PHPUnit\Framework\TestCase;
use stdClass;
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

    public function testSendTransitionIsAppliedWhenAllowed(): void
    {
        $slip = new stdClass(); // Dummy slip object

        $this->slipWorkflow->expects($this->once()) // Only called in apply()
            ->method('can')
            ->with($slip, 'send')
            ->willReturn(true);

        $this->slipWorkflow->expects($this->once())
            ->method('apply')
            ->with($slip, 'send');

        $this->service->send($slip);
    }

    public function testSendTransitionThrowsExceptionWhenNotAllowed(): void
    {
        $slip = new stdClass(); // Dummy slip object

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

    public function testPayTransitionIsAppliedWhenAllowed(): void
    {
        $slip = new stdClass(); // Dummy slip object

        $this->slipWorkflow->expects($this->exactly(2)) // Called in pay() and then in apply()
            ->method('can')
            ->with($slip, 'pay_from_submitted')
            ->willReturn(true);

        $this->slipWorkflow->expects($this->once())
            ->method('apply')
            ->with($slip, 'pay_from_submitted');

        $this->service->pay($slip);
    }

    public function testPayTransitionDoesNothingWhenNotAllowed(): void
    {
        $slip = new stdClass(); // Dummy slip object

        $this->slipWorkflow->expects($this->once())
            ->method('can')
            ->with($slip, 'pay_from_submitted')
            ->willReturn(false);

        $this->slipWorkflow->expects($this->never())
            ->method('apply');

        // No exception is expected for 'pay' when not allowed, it just does nothing
        $this->service->pay($slip);
    }

    public function testExpireTransitionIsAppliedWhenAllowed(): void
    {
        $slip = new stdClass(); // Dummy slip object

        $this->slipWorkflow->expects($this->once()) // Only called in apply()
            ->method('can')
            ->with($slip, 'expire')
            ->willReturn(true);

        $this->slipWorkflow->expects($this->once())
            ->method('apply')
            ->with($slip, 'expire');

        $this->service->expire($slip);
    }

    public function testExpireTransitionThrowsExceptionWhenNotAllowed(): void
    {
        $slip = new stdClass(); // Dummy slip object

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

    public function testCancelTransitionIsAppliedWhenAllowed(): void
    {
        $slip = new stdClass(); // Dummy slip object

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

    public function testCancelTransitionThrowsExceptionWhenNotAllowedFromAnyState(): void
    {
        $slip = new stdClass(); // Dummy slip object

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
