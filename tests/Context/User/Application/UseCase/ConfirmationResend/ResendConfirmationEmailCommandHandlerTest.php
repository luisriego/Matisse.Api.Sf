<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Application\UseCase\ConfirmationResend;

use App\Context\User\Application\Service\UserMailerInterface;
use App\Context\User\Application\UseCase\ConfirmationResend\ResendConfirmationEmailCommand;
use App\Context\User\Application\UseCase\ConfirmationResend\ResendConfirmationEmailCommandHandler;
use App\Context\User\Domain\UserRepository;
use App\Tests\Context\User\Domain\UserMother;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function is_string;

final class ResendConfirmationEmailCommandHandlerTest extends TestCase
{
    private MockObject|UserRepository $userRepository;
    private MockObject|UserMailerInterface $userMailer;
    private ResendConfirmationEmailCommandHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->userMailer = $this->createMock(UserMailerInterface::class);
        $this->handler = new ResendConfirmationEmailCommandHandler(
            $this->userRepository,
            $this->userMailer,
        );
    }

    public function testItShouldRefreshTokenSaveAndSendEmailForInactiveUser(): void
    {
        $user = UserMother::createRandom();
        $originalToken = $user->getConfirmationToken();
        $command = new ResendConfirmationEmailCommand($user->getEmail());

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
            ->method('sendConfirmationEmail')
            ->with(
                $user->getEmail(),
                $user->getName(),
                $user->getId(),
                $this->callback(fn (mixed $v): bool => is_string($v) && $v !== $originalToken),
            );

        ($this->handler)($command);
    }

    public function testItShouldDoNothingWhenUserNotFound(): void
    {
        $command = new ResendConfirmationEmailCommand('missing@example.com');

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);

        $this->userRepository->expects($this->never())->method('save');
        $this->userMailer->expects($this->never())->method('sendConfirmationEmail');

        ($this->handler)($command);
    }

    public function testItShouldDoNothingWhenUserIsAlreadyActive(): void
    {
        $user = UserMother::createRandom();
        $user->activate();
        $command = new ResendConfirmationEmailCommand($user->getEmail());

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn($user);

        $this->userRepository->expects($this->never())->method('save');
        $this->userMailer->expects($this->never())->method('sendConfirmationEmail');
        $this->userMailer->expects($this->never())->method('sendPasswordResetEmail');

        ($this->handler)($command);
    }
}
