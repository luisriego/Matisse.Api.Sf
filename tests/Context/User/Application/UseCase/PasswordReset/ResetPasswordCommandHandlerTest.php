<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Application\UseCase\PasswordReset;

use App\Context\User\Application\UseCase\PasswordReset\ResetPasswordCommand;
use App\Context\User\Application\UseCase\PasswordReset\ResetPasswordCommandHandler;
use App\Context\User\Domain\UserRepository;
use App\Shared\Domain\Clock;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Tests\Context\User\Domain\UserMother;
use App\Tests\Shared\Infrastructure\FixedClock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ResetPasswordCommandHandlerTest extends TestCase
{
    private MockObject|UserRepository $userRepository;
    private MockObject|UserPasswordHasherInterface $passwordHasher;
    private Clock $clock;
    private ResetPasswordCommandHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->clock = new FixedClock();
        $this->handler = new ResetPasswordCommandHandler(
            $this->userRepository,
            $this->passwordHasher,
            $this->clock,
            3600, // 1 hora de vida del token
        );
    }

    public function testItShouldResetPasswordSuccessfully(): void
    {
        $user = UserMother::createRandom();
        $user->requestPasswordReset();
        $token = $user->getPasswordResetToken();
        $newPassword = 'new-secure-password';

        $command = new ResetPasswordCommand($user->getId(), $token, $newPassword);

        $this->userRepository
            ->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($user->getId())
            ->willReturn($user);

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, $newPassword)
            ->willReturn('hashed_new_password');

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user, true);

        ($this->handler)($command);
    }

    public function testItShouldThrowWhenTokenIsNull(): void
    {
        $user = UserMother::createRandom();
        // No llamar requestPasswordReset() - token y requestedAt serán null

        $command = new ResetPasswordCommand($user->getId(), 'any-token', 'new-password');

        $this->userRepository
            ->method('findOneByIdOrFail')
            ->willReturn($user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Token de redefinição de senha inválido ou expirado.');

        ($this->handler)($command);
    }

    public function testItShouldThrowWhenTokenDoesNotMatch(): void
    {
        $user = UserMother::createRandom();
        $user->requestPasswordReset();

        $command = new ResetPasswordCommand($user->getId(), 'wrong-token', 'new-password');

        $this->userRepository
            ->method('findOneByIdOrFail')
            ->willReturn($user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Token de redefinição de senha inválido.');

        $this->userRepository->expects($this->never())->method('save');

        ($this->handler)($command);
    }

    public function testItShouldThrowWhenTokenIsExpired(): void
    {
        $user = UserMother::createRandom();
        $user->requestPasswordReset();

        // FixedClock en el futuro: el token (creado "ahora") ya habrá expirado
        $this->clock = new FixedClock('+2 hours');
        $this->handler = new ResetPasswordCommandHandler(
            $this->userRepository,
            $this->passwordHasher,
            $this->clock,
            3600, // 1 hora - el token creado hace 2 horas ya expiró
        );

        $command = new ResetPasswordCommand(
            $user->getId(),
            $user->getPasswordResetToken(),
            'new-password',
        );

        $this->userRepository
            ->method('findOneByIdOrFail')
            ->willReturn($user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Token de redefinição de senha expirado.');

        $this->userRepository->expects($this->never())->method('save');

        ($this->handler)($command);
    }

    public function testItShouldPropagateWhenUserNotFound(): void
    {
        $this->userRepository
            ->method('findOneByIdOrFail')
            ->willThrowException(new ResourceNotFoundException('User not found'));

        $this->expectException(ResourceNotFoundException::class);

        ($this->handler)(new ResetPasswordCommand('non-existent-id', 'token', 'new-pass'));
    }
}
