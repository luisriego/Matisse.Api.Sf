<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Domain\Service;

use App\Context\Slip\Domain\Exception\PeriodAlreadyClosedException;
use App\Context\Slip\Domain\PeriodClosureRepository;
use App\Context\Slip\Domain\Service\PeriodClosureGuard;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PeriodClosureGuardTest extends TestCase
{
    private MockObject|PeriodClosureRepository $repository;
    private PeriodClosureGuard $guard;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PeriodClosureRepository::class);
        $this->guard = new PeriodClosureGuard($this->repository);
    }

    public function testAssertNotClosedPassesWhenPeriodIsOpen(): void
    {
        $this->repository->method('existsForMonth')->with(2025, 1)->willReturn(false);

        $this->guard->assertNotClosed(2025, 1);
        $this->addToAssertionCount(1);
    }

    public function testAssertNotClosedThrowsWhenPeriodIsClosed(): void
    {
        $this->repository->method('existsForMonth')->with(2025, 1)->willReturn(true);

        $this->expectException(PeriodAlreadyClosedException::class);
        $this->guard->assertNotClosed(2025, 1);
    }
}
