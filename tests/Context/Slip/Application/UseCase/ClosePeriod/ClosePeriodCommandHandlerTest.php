<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Application\UseCase\ClosePeriod;

use App\Context\Slip\Application\UseCase\ClosePeriod\ClosePeriodCommand;
use App\Context\Slip\Application\UseCase\ClosePeriod\ClosePeriodCommandHandler;
use App\Context\Slip\Domain\Exception\NoSlipsToCloseException;
use App\Context\Slip\Domain\Exception\PeriodAlreadyClosedException;
use App\Context\Slip\Domain\PeriodClosure;
use App\Context\Slip\Domain\PeriodClosureRepository;
use App\Context\Slip\Domain\Service\PeriodClosureGuard;
use App\Context\Slip\Domain\Slip;
use App\Context\Slip\Domain\SlipRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClosePeriodCommandHandlerTest extends TestCase
{
    private MockObject|PeriodClosureRepository $periodClosureRepository;
    private MockObject|SlipRepository $slipRepository;
    private ClosePeriodCommandHandler $handler;

    protected function setUp(): void
    {
        $this->periodClosureRepository = $this->createMock(PeriodClosureRepository::class);
        $this->slipRepository = $this->createMock(SlipRepository::class);

        $guard = new PeriodClosureGuard($this->periodClosureRepository);

        $this->handler = new ClosePeriodCommandHandler(
            $this->periodClosureRepository,
            $this->slipRepository,
            $guard,
        );
    }

    public function testClosesPeriodSuccessfully(): void
    {
        $this->periodClosureRepository->method('existsForMonth')->willReturn(false);

        $slip = $this->createMock(Slip::class);
        $this->slipRepository->method('findByMonthYear')->with(2025, 2)->willReturn([$slip]);

        $this->periodClosureRepository->expects($this->once())->method('save')
            ->with($this->isInstanceOf(PeriodClosure::class));

        ($this->handler)(new ClosePeriodCommand(2025, 1));
    }

    public function testThrowsWhenPeriodAlreadyClosed(): void
    {
        $this->periodClosureRepository->method('existsForMonth')->willReturn(true);

        $this->expectException(PeriodAlreadyClosedException::class);
        ($this->handler)(new ClosePeriodCommand(2025, 1));
    }

    public function testThrowsWhenNoSlipsExistForPeriod(): void
    {
        $this->periodClosureRepository->method('existsForMonth')->willReturn(false);
        $this->slipRepository->method('findByMonthYear')->willReturn([]);

        $this->expectException(NoSlipsToCloseException::class);
        ($this->handler)(new ClosePeriodCommand(2025, 1));
    }
}
