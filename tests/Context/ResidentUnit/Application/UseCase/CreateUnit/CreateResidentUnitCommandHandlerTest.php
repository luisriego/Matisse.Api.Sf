<?php

namespace App\Tests\Context\ResidentUnit\Application\UseCase\CreateUnit;

use App\Context\ResidentUnit\Application\UseCase\CreateUnit\CreateResidentUnitCommand;
use App\Context\ResidentUnit\Application\UseCase\CreateUnit\CreateResidentUnitCommandHandler;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitIdealFractionMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitIdMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitVOMother;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use App\Context\ResidentUnit\Domain\ResidentUnitId;
use App\Context\ResidentUnit\Domain\ResidentUnitIdealFraction;

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

    public function test_it_should_create_a_resident_unit_successfully(): void
    {
        $id = ResidentUnitIdMother::create();
        $unit = ResidentUnitVOMother::create();
        $idealFraction = ResidentUnitIdealFractionMother::create(0.1);

        $command = new CreateResidentUnitCommand(
            $id->value(),
            $unit->value(),
            $idealFraction->value()
        );

        $this->repository->expects($this->once())
            ->method('calculateTotalIdealFraction')
            ->willReturn(0.5); // Ensure total ideal fraction is less than 1.0

        $this->repository->expects($this->once())
            ->method('save')
            ->with(self::isInstanceOf(ResidentUnit::class), true);

        ($this->handler)($command);
    }

    public function test_it_throws_exception_when_ideal_fraction_exceeds_one(): void
    {
        $this->repository
            ->method('calculateTotalIdealFraction')
            ->willReturn(0.9); // Mocked total ideal fraction

        $command = new CreateResidentUnitCommand(
            'unit-id',
            'Unit Name',
            0.2 // New fraction that exceeds the limit
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ideal fraction must not be more than 1');

        $this->handler->__invoke($command);
    }

    public function test_it_should_throw_exception_if_id_is_invalid(): void
    {
        $invalidId = 'not-a-valid-uuid';
        $unit = ResidentUnitVOMother::create();
        $idealFraction = ResidentUnitIdealFractionMother::create(0.1);

        $command = new CreateResidentUnitCommand(
            $invalidId,
            $unit->value(),
            $idealFraction->value()
        );

        // Expect calculateTotalIdealFraction to be called, as the ID validation happens after this
        $this->repository->expects($this->once())
            ->method('calculateTotalIdealFraction')
            ->willReturn(0.0); // Return a dummy value

        $this->repository->expects($this->never())->method('save');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('<%s> does not allow the value <%s>.', \App\Context\ResidentUnit\Domain\ResidentUnitId::class, $invalidId));

        ($this->handler)($command);
    }

    public function test_it_should_throw_exception_if_ideal_fraction_is_not_between_zero_and_one(): void
    {
        $id = ResidentUnitIdMother::create();
        $unit = ResidentUnitVOMother::create();
        $invalidIdealFraction = -0.1; // Test with negative or > 1

        $command = new CreateResidentUnitCommand(
            $id->value(),
            $unit->value(),
            $invalidIdealFraction
        );

        // Expect calculateTotalIdealFraction to NOT be called, as the validation happens before this
        $this->repository->expects($this->never())
            ->method('calculateTotalIdealFraction');

        $this->repository->expects($this->never())->method('save');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A fracao ideal deve ser maior o igual que zero e menor ou igual que um');

        ($this->handler)($command);
    }
}
