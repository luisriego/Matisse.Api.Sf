<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Application\UseCase\Registration;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\User\Application\UseCase\Registration\RegisterUserCommand;
use App\Context\User\Application\UseCase\Registration\RegisterUserCommandHandler;
use App\Context\User\Domain\Event\UserWasRegistered;
use App\Context\User\Domain\User;
use App\Context\User\Domain\UserRepository;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\Exception\ResourceAlreadyExistException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Tests\Context\User\Domain\ValueObject\EmailMother;
use App\Tests\Context\User\Domain\ValueObject\PasswordMother;
use App\Tests\Context\User\Domain\ValueObject\UserIdMother;
use App\Tests\Context\User\Domain\ValueObject\UserNameMother;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterUserCommandHandlerTest extends TestCase
{
    private MockObject|UserRepository $userRepository;
    private MockObject|UserPasswordHasherInterface $passwordHasher;
    private MockObject|EventBus $eventBus;
    private MockObject|ResidentUnitRepository $residentUnitRepository;
    private RegisterUserCommandHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->eventBus = $this->createMock(EventBus::class);
        $this->residentUnitRepository = $this->createMock(ResidentUnitRepository::class);

        $this->handler = new RegisterUserCommandHandler(
            $this->userRepository,
            $this->passwordHasher,
            $this->eventBus,
            $this->residentUnitRepository
        );
    }

    public function test_it_should_register_a_user_and_publish_event(): void
    {
        $command = $this->createRegisterUserCommand();

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($command->email())
            ->willReturn(null);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $user) use ($command) {
                $events = $user->pullDomainEvents();
                if (count($events) !== 1) return false;
                $event = $events[0];
                return $event instanceof UserWasRegistered
                    && $event->email() === $command->email()
                    && $event->name() === $command->name()
                    && is_string($event->aggregateId())
                    && is_string($event->confirmationToken());
            }));

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->eventBus->expects($this->never())->method('publish');

        $this->residentUnitRepository->expects($this->never())->method('findOneById');

        ($this->handler)($command);
    }

    public function test_it_should_register_a_user_with_resident_unit_id(): void
    {
        $residentUnitId = 'some-resident-unit-id';
        $command = $this->createRegisterUserCommand($residentUnitId);
        $residentUnit = $this->createMock(ResidentUnit::class);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($command->email())
            ->willReturn(null);

        $this->residentUnitRepository
            ->expects($this->once())
            ->method('findOneById')
            ->with($residentUnitId)
            ->willReturn($residentUnit);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $user) {
                $events = $user->pullDomainEvents();
                return count($events) === 1 && $events[0] instanceof UserWasRegistered;
            }));

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->eventBus->expects($this->never())->method('publish');

        ($this->handler)($command);
    }

    public function test_it_should_throw_exception_when_user_already_exists(): void
    {
        $this->expectException(ResourceAlreadyExistException::class);

        $command = $this->createRegisterUserCommand();
        $existingUser = $this->createMock(User::class);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($command->email())
            ->willReturn($existingUser);

        $this->userRepository->expects($this->never())->method('save');
        $this->eventBus->expects($this->never())->method('publish');
        $this->residentUnitRepository->expects($this->never())->method('findOneById');

        ($this->handler)($command);
    }

    public function test_it_should_throw_exception_when_resident_unit_not_found(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        $residentUnitId = 'non-existent-resident-unit-id';
        $command = $this->createRegisterUserCommand($residentUnitId);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($command->email())
            ->willReturn(null);

        $this->residentUnitRepository
            ->expects($this->once())
            ->method('findOneById')
            ->with($residentUnitId)
            ->willReturn(null);

        $this->userRepository->expects($this->never())->method('save');
        $this->eventBus->expects($this->never())->method('publish');

        ($this->handler)($command);
    }

    private function createRegisterUserCommand(?string $residentUnitId = null): RegisterUserCommand
    {
        return new RegisterUserCommand(
            UserIdMother::create()->value(),
            UserNameMother::create()->value(),
            EmailMother::create()->value(),
            PasswordMother::create()->value(),
            $residentUnitId
        );
    }
}
