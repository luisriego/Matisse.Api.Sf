<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Application\UseCase\LinkResidentUnit;

use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\User\Application\UseCase\ResidentUnit\LinkResidentUnitToUserCommand;
use App\Context\User\Application\UseCase\ResidentUnit\LinkResidentUnitToUserCommandHandler;
use App\Context\User\Domain\UserRepository;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Context\User\Domain\UserMother;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class LinkResidentUnitToUserCommandHandlerTest extends TestCase
{
    private MockObject|UserRepository $userRepository;
    private MockObject|ResidentUnitRepository $residentUnitRepository;
    private LinkResidentUnitToUserCommandHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->residentUnitRepository = $this->createMock(ResidentUnitRepository::class);
        $this->handler = new LinkResidentUnitToUserCommandHandler(
            $this->userRepository,
            $this->residentUnitRepository,
        );
    }

    public function testItShouldLinkResidentUnitToUserSuccessfully(): void
    {
        $user = UserMother::createRandom();
        $residentUnit = ResidentUnitMother::create();
        $command = new LinkResidentUnitToUserCommand($user->getId(), $residentUnit->id());

        $this->userRepository
            ->expects($this->once())
            ->method('findOneById')
            ->with($user->getId())
            ->willReturn($user);

        $this->residentUnitRepository
            ->expects($this->once())
            ->method('findOneById')
            ->with($residentUnit->id())
            ->willReturn($residentUnit);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user, true);

        ($this->handler)($command);
    }

    public function testItShouldThrowWhenUserNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User not found');

        $command = new LinkResidentUnitToUserCommand('non-existent-user-id', 'some-resident-unit-id');

        $this->userRepository
            ->method('findOneById')
            ->with('non-existent-user-id')
            ->willReturn(null);

        $this->residentUnitRepository->expects($this->never())->method('findOneById');

        ($this->handler)($command);
    }

    public function testItShouldThrowWhenResidentUnitNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resident unit not found');

        $user = UserMother::createRandom();
        $command = new LinkResidentUnitToUserCommand($user->getId(), 'non-existent-resident-unit-id');

        $this->userRepository
            ->method('findOneById')
            ->with($user->getId())
            ->willReturn($user);

        $this->residentUnitRepository
            ->method('findOneById')
            ->with('non-existent-resident-unit-id')
            ->willReturn(null);

        $this->userRepository->expects($this->never())->method('save');

        ($this->handler)($command);
    }

    public function testItShouldNotSaveWhenUserAlreadyHasSameResidentUnit(): void
    {
        $residentUnit = ResidentUnitMother::create();
        $user = UserMother::createRandom();
        $user->setResidentUnit($residentUnit);

        $command = new LinkResidentUnitToUserCommand($user->getId(), $residentUnit->id());

        $this->userRepository
            ->expects($this->once())
            ->method('findOneById')
            ->with($user->getId())
            ->willReturn($user);

        $this->residentUnitRepository
            ->expects($this->once())
            ->method('findOneById')
            ->with($residentUnit->id())
            ->willReturn($residentUnit);

        // Idempotencia: no debe llamar a save
        $this->userRepository->expects($this->never())->method('save');

        ($this->handler)($command);
    }
}
