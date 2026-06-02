<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Application\UseCase\Update;

use App\Context\User\Application\UseCase\Update\UpdateUserCommand;
use App\Context\User\Application\UseCase\Update\UpdateUserCommandHandler;
use App\Context\User\Domain\UserRepository;
use App\Tests\Context\User\Domain\UserMother;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

final class UpdateUserCommandHandlerTest extends TestCase
{
    private MockObject|UserRepository $userRepository;
    private UpdateUserCommandHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->handler = new UpdateUserCommandHandler($this->userRepository);
    }

    public function testItShouldUpdateUserSuccessfully(): void
    {
        $user = UserMother::createRandom();
        $command = new UpdateUserCommand(
            $user->getId(),
            'Updated Name',
            'Updated LastName',
            'M',
            '+351912345678',
        );

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
        $this->expectException(UserNotFoundException::class);

        $command = new UpdateUserCommand(
            'non-existent-id',
            'Name',
            'LastName',
            'F',
            '123456789',
        );

        $this->userRepository
            ->method('findOneById')
            ->with('non-existent-id')
            ->willReturn(null);

        $this->userRepository->expects($this->never())->method('save');

        ($this->handler)($command);
    }
}
