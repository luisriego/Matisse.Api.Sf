<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Application\UseCase\ChangePassword;

use App\Context\User\Application\UseCase\ChangePassword\ChangePasswordCommand;
use App\Context\User\Application\UseCase\ChangePassword\ChangePasswordCommandHandler;
use App\Context\User\Domain\UserRepository;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Tests\Context\User\Domain\UserMother;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ChangePasswordCommandHandlerTest extends TestCase
{
    private MockObject|UserRepository $userRepository;
    private MockObject|UserPasswordHasherInterface $passwordHasher;
    private ChangePasswordCommandHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository  = $this->createMock(UserRepository::class);
        $this->passwordHasher  = $this->createMock(UserPasswordHasherInterface::class);
        $this->handler = new ChangePasswordCommandHandler(
            $this->userRepository,
            $this->passwordHasher,
        );
    }

    public function testItShouldChangePasswordSuccessfully(): void
    {
        $user    = UserMother::createRandom();
        $command = new ChangePasswordCommand($user->getEmail(), 'old_pass', 'new_pass');

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($user->getEmail())
            ->willReturn($user);

        $this->passwordHasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, 'old_pass')
            ->willReturn(true);

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_new_pass');

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user, true);

        ($this->handler)($command);
    }

    public function testItShouldThrowIfUserNotFound(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        $command = new ChangePasswordCommand('nonexistent@mail.com', 'old', 'new');

        $this->userRepository
            ->method('findByEmail')
            ->willReturn(null);

        ($this->handler)($command);
    }

    public function testItShouldThrowIfOldPasswordIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid old password.');

        $user    = UserMother::createRandom();
        $command = new ChangePasswordCommand($user->getEmail(), 'wrong_old_pass', 'new_pass');

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->passwordHasher
            ->method('isPasswordValid')
            ->willReturn(false);

        ($this->handler)($command);
    }
}
