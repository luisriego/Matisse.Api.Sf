<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Application\UseCase\PasswordReset;

use App\Context\User\Application\Service\UserMailerInterface;
use App\Context\User\Application\UseCase\PasswordReset\PasswordResetRequestCommand;
use App\Context\User\Application\UseCase\PasswordReset\PasswordResetRequestCommandHandler;
use App\Context\User\Domain\UserRepository;
use App\Tests\Context\User\Domain\UserMother;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function is_string;

final class PasswordResetRequestCommandHandlerTest extends TestCase
{
    private MockObject|UserRepository $userRepository;
    private MockObject|UserMailerInterface $userMailer;
    private PasswordResetRequestCommandHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->userMailer = $this->createMock(UserMailerInterface::class);
        $this->handler = new PasswordResetRequestCommandHandler(
            $this->userRepository,
            $this->userMailer,
        );
    }

    public function testItShouldGenerateTokenSaveAndSendEmailWhenUserExists(): void
    {
        $user = UserMother::createRandom();
        $command = new PasswordResetRequestCommand($user->getEmail());

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($user->getEmail())
            ->willReturn($user);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user, true);

        $this->userMailer
            ->expects($this->once())
            ->method('sendPasswordResetEmail')
            ->with(
                $user->getEmail(),
                $user->getName(),
                $user->getId(),
                $this->callback(fn (mixed $v): bool => is_string($v)),
            );

        ($this->handler)($command);
    }

    public function testItShouldDoNothingWhenUserNotFound(): void
    {
        $command = new PasswordResetRequestCommand('nonexistent@example.com');

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('nonexistent@example.com')
            ->willReturn(null);

        $this->userRepository->expects($this->never())->method('save');
        $this->userMailer->expects($this->never())->method('sendPasswordResetEmail');

        ($this->handler)($command);
    }
}
