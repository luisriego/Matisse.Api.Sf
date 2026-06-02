<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Application\UseCase\CreateUnit;

use App\Context\ResidentUnit\Application\UseCase\CreateUnit\CreateResidentUnitCommand;
use App\Context\ResidentUnit\Application\UseCase\CreateUnit\CreateResidentUnitCommandHandler;
use App\Context\ResidentUnit\Domain\Exception\IdealFractionSumExceedsLimitException;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitId;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitIdealFractionMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitIdMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitVOMother;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

use function sprintf;

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

    public function testItShouldCreateAResidentUnitSuccessfully(): void
    {
        $id = ResidentUnitIdMother::create();
        $unit = ResidentUnitVOMother::create();
        $idealFraction = ResidentUnitIdealFractionMother::create(0.1);

        $command = new CreateResidentUnitCommand(
            $id->value(),
            $unit->value(),
            $idealFraction->value(),
        );

        $this->repository->expects($this->once())
            ->method('calculateTotalIdealFraction')
            ->willReturn(0.5); // Ensure total ideal fraction is less than 1.0

        // Mock the exists method to return false, indicating the unit does not exist
        $this->repository->expects($this->once())
            ->method('exists')
            ->with(self::isInstanceOf(ResidentUnitId::class))
            ->willReturn(false);

        $this->repository->expects($this->once())
            ->method('save')
            ->with(self::isInstanceOf(ResidentUnit::class), true);

        ($this->handler)($command);
    }

    public function testItAcceptsTotalJustAboveOneWithinFloatingPointTolerance(): void
    {
        $id = ResidentUnitIdMother::create();

        $this->repository
            ->method('calculateTotalIdealFraction')
            ->willReturn(0.99999995);

        $this->repository->expects($this->once())
            ->method('exists')
            ->with(self::isInstanceOf(ResidentUnitId::class))
            ->willReturn(false);

        $this->repository->expects($this->once())
            ->method('save')
            ->with(self::isInstanceOf(ResidentUnit::class), true);

        $command = new CreateResidentUnitCommand(
            $id->value(),
            'Unit Name',
            0.0000001,
        );

        ($this->handler)($command);
    }

    public function testItThrowsExceptionWhenIdealFractionExceedsOne(): void
    {
        // Use a valid ID for this test
        $id = ResidentUnitIdMother::create();

        $this->repository
            ->method('calculateTotalIdealFraction')
            ->willReturn(0.9); // Mocked total ideal fraction

        // Mock the exists method to return false, indicating the unit does not exist
        $this->repository->expects($this->once())
            ->method('exists')
            ->with(self::isInstanceOf(ResidentUnitId::class))
            ->willReturn(false);

        $command = new CreateResidentUnitCommand(
            $id->value(), // Use a valid ID
            'Unit Name',
            0.2, // New fraction that exceeds the limit
        );

        $this->expectException(IdealFractionSumExceedsLimitException::class);
        $this->expectExceptionMessage('A soma das frações ideais não pode ser maior que 1.');

        $this->handler->__invoke($command);
    }

    public function testItShouldThrowExceptionIfIdIsInvalid(): void
    {
        $invalidId = 'not-a-valid-uuid';
        $unit = ResidentUnitVOMother::create();
        $idealFraction = ResidentUnitIdealFractionMother::create(0.1);

        $command = new CreateResidentUnitCommand(
            $invalidId,
            $unit->value(),
            $idealFraction->value(),
        );

        // The exists method will NOT be called because ResidentUnitId constructor will throw an exception first
        $this->repository->expects($this->never())->method('exists');
        $this->repository->expects($this->never())->method('calculateTotalIdealFraction');
        $this->repository->expects($this->never())->method('save');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('<%s> does not allow the value <%s>.', ResidentUnitId::class, $invalidId));

        ($this->handler)($command);
    }

    public function testItShouldThrowExceptionIfIdealFractionIsNotBetweenZeroAndOne(): void
    {
        $id = ResidentUnitIdMother::create();
        $unit = ResidentUnitVOMother::create();
        $invalidIdealFraction = -0.1; // Test with negative or > 1

        $command = new CreateResidentUnitCommand(
            $id->value(),
            $unit->value(),
            $invalidIdealFraction,
        );

        // Mock the exists method to return false, indicating the unit does not exist
        $this->repository->expects($this->once())
            ->method('exists')
            ->with(self::isInstanceOf(ResidentUnitId::class))
            ->willReturn(false);

        // Expect calculateTotalIdealFraction to NOT be called, as the validation happens before this
        $this->repository->expects($this->never())
            ->method('calculateTotalIdealFraction');

        $this->repository->expects($this->never())->method('save');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A fração ideal deve ser maior ou igual a zero e menor ou igual a um.');

        ($this->handler)($command);
    }
}
