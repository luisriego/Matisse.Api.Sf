<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Application\UseCase\UnlinkResidentUnit;

use App\Context\User\Application\UseCase\ResidentUnit\UnlinkResidentUnitFromUserCommand;
use App\Context\User\Application\UseCase\ResidentUnit\UnlinkResidentUnitFromUserCommandHandler;
use App\Context\User\Domain\UserRepository;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Context\User\Domain\UserMother;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class UnlinkResidentUnitFromUserCommandHandlerTest extends TestCase
{
    private MockObject|UserRepository $userRepository;
    private UnlinkResidentUnitFromUserCommandHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->handler = new UnlinkResidentUnitFromUserCommandHandler($this->userRepository);
    }

    public function testItShouldUnlinkResidentUnitFromUserSuccessfully(): void
    {
        $residentUnit = ResidentUnitMother::create();
        $user = UserMother::createRandom();
        $user->setResidentUnit($residentUnit);

        $command = new UnlinkResidentUnitFromUserCommand($user->getId());

        $this->userRepository
            ->expects($this->once())
            ->method('findOneById')
            ->with($user->getId())
            ->willReturn($user);

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

        $command = new UnlinkResidentUnitFromUserCommand('non-existent-user-id');

        $this->userRepository
            ->method('findOneById')
            ->with('non-existent-user-id')
            ->willReturn(null);

        $this->userRepository->expects($this->never())->method('save');

        ($this->handler)($command);
    }

    public function testItShouldNotSaveWhenUserHasNoResidentUnit(): void
    {
        $user = UserMother::createRandom();

        $command = new UnlinkResidentUnitFromUserCommand($user->getId());

        $this->userRepository
            ->expects($this->once())
            ->method('findOneById')
            ->with($user->getId())
            ->willReturn($user);

        $this->userRepository->expects($this->never())->method('save');

        ($this->handler)($command);
    }
}
