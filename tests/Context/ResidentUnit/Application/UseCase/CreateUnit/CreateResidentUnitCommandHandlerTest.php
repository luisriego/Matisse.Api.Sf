<?php

namespace App\Tests\Context\ResidentUnit\Application\UseCase\CreateUnit;

use App\Context\ResidentUnit\Application\UseCase\CreateUnit\CreateResidentUnitCommand;
use App\Context\ResidentUnit\Application\UseCase\CreateUnit\CreateResidentUnitCommandHandler;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class CreateResidentUnitCommandHandlerTest extends TestCase
{
    private ResidentUnitRepository $repository;
    private CreateResidentUnitCommandHandler $handler;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->repository = $this->createMock(ResidentUnitRepository::class);
        $this->handler = new CreateResidentUnitCommandHandler($this->repository);
    }

    public function test_it_throws_exception_when_ideal_fraction_exceeds_one(): void
    {
        $this->repository
            ->method('calculateTotalIdealFraction')
            ->willReturn(0.9); // Mocked total ideal fraction

        $command = new CreateResidentUnitCommand(
            'unit-id',
            'Unit Name',
            0.2, // New fraction that exceeds the limit
            []
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ideal fraction must not be more than 1');

        $this->handler->__invoke($command);
    }
}
