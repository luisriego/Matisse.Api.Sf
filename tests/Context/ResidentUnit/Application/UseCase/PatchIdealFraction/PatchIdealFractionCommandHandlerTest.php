<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Application\UseCase\PatchIdealFraction;

use App\Context\ResidentUnit\Application\UseCase\PatchIdealFraction\PatchIdealFractionCommand;
use App\Context\ResidentUnit\Application\UseCase\PatchIdealFraction\PatchIdealFractionCommandHandler;
use App\Context\ResidentUnit\Domain\Exception\IdealFractionSumExceedsLimitException;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use PHPUnit\Framework\TestCase;

final class PatchIdealFractionCommandHandlerTest extends TestCase
{
    private ResidentUnitRepository $repository;
    private PatchIdealFractionCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ResidentUnitRepository::class);
        $this->handler = new PatchIdealFractionCommandHandler($this->repository);
    }

    public function test_it_throws_exception_if_resident_unit_not_found(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        $this->repository->method('findOneByIdOrFail')->willThrowException(new ResourceNotFoundException());

        $command = new PatchIdealFractionCommand('non-existent-id', 0.1);
        ($this->handler)($command);
    }

    public function test_it_throws_exception_if_ideal_fraction_sum_exceeds_limit(): void
    {
        $this->expectException(IdealFractionSumExceedsLimitException::class);

        $residentUnit = $this->createMock(ResidentUnit::class);
        $this->repository->method('findOneByIdOrFail')->willReturn($residentUnit);
        $this->repository->method('calculateTotalIdealFraction')->willReturn(0.9);

        $command = new PatchIdealFractionCommand('some-id', 0.2); // 0.9 + 0.2 > 1
        ($this->handler)($command);
    }

    public function test_it_throws_exception_for_negative_ideal_fraction(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $command = new PatchIdealFractionCommand('some-id', -0.1);
        ($this->handler)($command);
    }

    public function test_it_throws_exception_for_ideal_fraction_greater_than_one(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $command = new PatchIdealFractionCommand('some-id', 1.1);
        ($this->handler)($command);
    }

    public function test_it_updates_ideal_fraction_successfully(): void
    {
        $residentUnit = $this->createMock(ResidentUnit::class);
        $this->repository->method('findOneByIdOrFail')->willReturn($residentUnit);
        $this->repository->method('calculateTotalIdealFraction')->willReturn(0.8);

        $residentUnit->expects($this->once())->method('changeIdealFraction');
        $this->repository->expects($this->once())->method('save')->with($residentUnit);

        $command = new PatchIdealFractionCommand('some-id', 0.1); // 0.8 + 0.1 <= 1
        ($this->handler)($command);
    }

    public function test_it_updates_when_ideal_fraction_sum_is_exactly_one(): void
    {
        $residentUnit = $this->createMock(ResidentUnit::class);
        $this->repository->method('findOneByIdOrFail')->willReturn($residentUnit);
        $this->repository->method('calculateTotalIdealFraction')->willReturn(0.9);

        $residentUnit->expects($this->once())->method('changeIdealFraction');
        $this->repository->expects($this->once())->method('save');

        $command = new PatchIdealFractionCommand('some-id', 0.1); // 0.9 + 0.1 = 1.0
        ($this->handler)($command);
    }
}
