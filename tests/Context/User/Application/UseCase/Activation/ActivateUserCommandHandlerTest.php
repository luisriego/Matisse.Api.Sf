<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Application\UseCase\Activation;

use App\Context\User\Application\UseCase\Activation\ActivateUserCommand;
use App\Context\User\Application\UseCase\Activation\ActivateUserCommandHandler;
use App\Context\User\Domain\User;
use App\Context\User\Domain\UserRepository;
use App\Context\User\Domain\ValueObject\Email;
use App\Context\User\Domain\ValueObject\UserId;
use App\Context\User\Domain\ValueObject\UserName;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Tests\Context\User\Domain\UserMother;
use App\Tests\Context\User\Domain\ValueObject\UserIdMother;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ActivateUserCommandHandlerTest extends TestCase
{
    private const string APP_BASE_URL = 'https://matisse.test';
    private const string SIGN_IN_PATH = '/signin';
    private const string SET_PASSWORD_PATH = '/set-password';

    private MockObject|UserRepository $userRepository;
    private ActivateUserCommandHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->handler = new ActivateUserCommandHandler(
            $this->userRepository,
            self::APP_BASE_URL,
            self::SIGN_IN_PATH,
            self::SET_PASSWORD_PATH,
        );
    }

    public function testItShouldActivateAUserWithPasswordAndRedirectToSignIn(): void
    {
        $user = UserMother::createRandom();
        $command = new ActivateUserCommand($user->getId(), $user->getConfirmationToken());

        $this->userRepository
            ->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($user->getId())
            ->willReturn($user);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with(self::callback(static function (User $savedUser): bool {
                return true === $savedUser->isActive() && null === $savedUser->getConfirmationToken();
            }));

        $result = ($this->handler)($command);

        $this->assertSame(self::APP_BASE_URL . self::SIGN_IN_PATH, $result->redirectUrl);
    }

    public function testItShouldActivateInvitedUserAndRedirectToSetPassword(): void
    {
        $userId = UserIdMother::create();
        $user = User::invite(
            $userId,
            UserName::fromString('João'),
            Email::fromString('joao@example.com'),
            $this->createMock(\App\Context\ResidentUnit\Domain\ResidentUnit::class),
        );

        $command = new ActivateUserCommand((string) $userId->value(), $user->getConfirmationToken());

        $this->userRepository
            ->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with((string) $userId->value())
            ->willReturn($user);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with(self::callback(static function (User $savedUser): bool {
                return true === $savedUser->isActive()
                    && null !== $savedUser->getPasswordResetToken();
            }));

        $result = ($this->handler)($command);

        $this->assertStringStartsWith(self::APP_BASE_URL . self::SET_PASSWORD_PATH . '/', $result->redirectUrl);
    }

    public function testItShouldThrowExceptionIfUserNotFound(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        $command = new ActivateUserCommand('non-existent-id', 'some-token');

        $this->userRepository
            ->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with('non-existent-id')
            ->willThrowException(new ResourceNotFoundException());

        ($this->handler)($command);
    }

    public function testItShouldThrowExceptionForAnInvalidToken(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid confirmation token.');

        $user = UserMother::createRandom();
        $command = new ActivateUserCommand($user->getId(), 'this-is-a-wrong-token');

        $this->userRepository
            ->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($user->getId())
            ->willReturn($user);

        ($this->handler)($command);
    }
}
