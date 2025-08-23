<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Application\UseCase\SendSlip;

use App\Context\Slip\Application\UseCase\SendSlip\SlipSendCommand;
use App\Context\Slip\Application\UseCase\SendSlip\SlipSendCommandHandler;
use App\Context\Slip\Domain\Slip;
use App\Context\Slip\Domain\SlipRepository;
use App\Shared\Domain\Exception\NotFoundException;
use App\Tests\Context\Slip\Domain\SlipMother;
use App\Tests\Context\Slip\SlipModuleUnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\WorkflowInterface;

final class SlipSendCommandHandlerTest extends SlipModuleUnitTestCase
{
    private SlipSendCommandHandler $handler;
    private SlipRepository|MockObject $repository;
    private WorkflowInterface|MockObject $slipStateMachine;


    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->repository();
        $this->slipStateMachine = $this->createMock(WorkflowInterface::class);

        $this->handler = new SlipSendCommandHandler(
            $this->repository,
            $this->slipStateMachine
        );
    }

    /** @test */
    public function it_should_apply_send_transition_to_slip(): void
    {
        $slip = SlipMother::create();
        $command = new SlipSendCommand($slip->id());

        $this->repository->method('findOneByIdOrFail')->willReturn($slip);

        $this->slipStateMachine->expects(self::once())
            ->method('apply')
            ->with($slip, 'send');

        ($this->handler)($command);
    }

    /** @test */
    public function it_should_throw_an_exception_when_slip_not_found(): void
    {
        $this->expectException(NotFoundException::class);

        $slip = SlipMother::create();
        $command = new SlipSendCommand($slip->id());

        $this->repository->method('findOneByIdOrFail')
            ->willThrowException(new NotFoundException('Slip not found.'));

        $this->slipStateMachine->expects(self::never())
            ->method('apply');

        ($this->handler)($command);
    }

    /** @test */
    public function it_should_throw_an_exception_when_transition_is_not_valid(): void
    {
        $this->expectException(LogicException::class);

        $slip = SlipMother::create();
        $command = new SlipSendCommand($slip->id());

        $this->repository->method('findOneByIdOrFail')->willReturn($slip);

        $this->slipStateMachine->expects(self::once())
            ->method('apply')
            ->with($slip, 'send')
            ->willThrowException(new LogicException('Transition "send" is not available.'));

        ($this->handler)($command);
    }
}
