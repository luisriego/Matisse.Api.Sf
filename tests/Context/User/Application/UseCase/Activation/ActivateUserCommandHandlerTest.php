<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Application\UseCase\Activation;

use App\Context\User\Application\UseCase\Activation\ActivateUserCommand;
use App\Context\User\Application\UseCase\Activation\ActivateUserCommandHandler;
use App\Context\User\Domain\User;
use App\Context\User\Domain\UserRepository;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Tests\Context\User\Domain\UserMother;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ActivateUserCommandHandlerTest extends TestCase
{
    private MockObject|UserRepository $userRepository;
    private ActivateUserCommandHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->handler = new ActivateUserCommandHandler($this->userRepository);
    }

    public function test_it_should_activate_a_user_with_a_valid_token(): void
    {
        // 1. Arrange
        $user = UserMother::createRandom(); // This user is inactive and has a token by default
        $command = new ActivateUserCommand($user->getId(), $user->getConfirmationToken());

        $this->userRepository
            ->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($user->getId())
            ->willReturn($user);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $savedUser) {
                // Assert that the user is now active and the token is gone
                return $savedUser->isActive() === true && $savedUser->getConfirmationToken() === null;
            }));

        // 2. Act
        ($this->handler)($command);
    }

    public function test_it_should_throw_exception_if_user_not_found(): void
    {
        // 1. Arrange
        $this->expectException(ResourceNotFoundException::class);

        $command = new ActivateUserCommand('non-existent-id', 'some-token');

        $this->userRepository
            ->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with('non-existent-id')
            ->willThrowException(new ResourceNotFoundException());

        // 2. Act
        ($this->handler)($command);
    }

    public function test_it_should_throw_exception_for_an_invalid_token(): void
    {
        // 1. Arrange
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid confirmation token.');

        $user = UserMother::createRandom();
        $command = new ActivateUserCommand($user->getId(), 'this-is-a-wrong-token');

        $this->userRepository
            ->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($user->getId())
            ->willReturn($user);

        // 2. Act
        ($this->handler)($command);
    }
}
