<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Application\UseCase\SendBulkSlips;

use App\Context\Slip\Application\UseCase\SendBulkSlips\SendBulkSlipsCommand;
use App\Context\Slip\Application\UseCase\SendBulkSlips\SendBulkSlipsCommandHandler;
use App\Context\Slip\Domain\Slip;
use App\Context\Slip\Domain\SlipRepository;
use App\Tests\Context\Slip\Domain\SlipMother;
use App\Tests\Context\Slip\SlipModuleUnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\WorkflowInterface;

final class SendBulkSlipsCommandHandlerTest extends SlipModuleUnitTestCase
{
    private SendBulkSlipsCommandHandler $handler;
    private MockObject|WorkflowInterface $slipStateMachine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->slipStateMachine = $this->createMock(WorkflowInterface::class);

        $this->handler = new SendBulkSlipsCommandHandler(
            $this->repository(),
            $this->slipStateMachine
        );
    }

    /** @test */
    public function test_it_should_apply_send_transition_to_multiple_slips(): void
    {
        $slip1 = SlipMother::create();
        $slip2 = SlipMother::create();
        $slips = [$slip1, $slip2];
        $slipIds = array_map(fn(Slip $slip) => $slip->id(), $slips);
        $command = new SendBulkSlipsCommand($slipIds);

        $this->repository()->method('findManyByIds')->with($slipIds)->willReturn($slips);

        $this->slipStateMachine->method('can')
            ->willReturnMap([
                [$slip1, 'send', true],
                [$slip2, 'send', true],
            ]);

        $capturedArgs = [];
        $this->slipStateMachine->expects(self::exactly(2))
            ->method('apply')
            ->willReturnCallback(function (...$args) use (&$capturedArgs) {
                $capturedArgs[] = $args;
                return $this->createMock(Marking::class);
            });

        ($this->handler)($command);

        self::assertCount(2, $capturedArgs);
        self::assertEquals([$slip1, 'send', []], $capturedArgs[0]);
        self::assertEquals([$slip2, 'send', []], $capturedArgs[1]);
    }

    /** @test */
    public function test_it_should_only_apply_transition_when_possible(): void
    {
        $validSlip = SlipMother::create();
        $invalidSlip = SlipMother::create();
        $slips = [$validSlip, $invalidSlip];
        $slipIds = array_map(fn(Slip $slip) => $slip->id(), $slips);
        $command = new SendBulkSlipsCommand($slipIds);

        $this->repository()->method('findManyByIds')->with($slipIds)->willReturn($slips);

        $this->slipStateMachine->method('can')
            ->willReturnMap([
                [$validSlip, 'send', true],
                [$invalidSlip, 'send', false],
            ]);

        $this->slipStateMachine->expects(self::once())
            ->method('apply')
            ->with($validSlip, 'send')
            ->willReturn($this->createMock(Marking::class));

        ($this->handler)($command);
    }
}