<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Application\UseCase\Registration;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\User\Application\Service\UserMailerInterface;
use App\Context\User\Application\UseCase\Registration\RegisterUserCommand;
use App\Context\User\Application\UseCase\Registration\RegisterUserCommandHandler;
use App\Context\User\Domain\User;
use App\Context\User\Domain\UserRepository;
use App\Shared\Domain\Exception\ResourceAlreadyExistException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Tests\Context\User\Domain\ValueObject\EmailMother;
use App\Tests\Context\User\Domain\ValueObject\PasswordMother;
use App\Tests\Context\User\Domain\ValueObject\UserIdMother;
use App\Tests\Context\User\Domain\ValueObject\UserNameMother;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterUserCommandHandlerTest extends TestCase
{
    private MockObject|UserRepository $userRepository;
    private MockObject|UserPasswordHasherInterface $passwordHasher;
    private MockObject|UserMailerInterface $userMailer;
    private MockObject|ResidentUnitRepository $residentUnitRepository; // Añadido: Mock para ResidentUnitRepository
    private RegisterUserCommandHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->userMailer = $this->createMock(UserMailerInterface::class);
        $this->residentUnitRepository = $this->createMock(ResidentUnitRepository::class); // Añadido: Creación del mock

        $this->handler = new RegisterUserCommandHandler(
            $this->userRepository,
            $this->passwordHasher,
            $this->userMailer,
            $this->residentUnitRepository
        );
    }

    public function test_it_should_register_a_user_and_send_confirmation_email(): void
    {
        // 1. Prepare test data
        $command = $this->createRegisterUserCommand();

        // 2. Set mock expectations
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($command->email())
            ->willReturn(null);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class));

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->userMailer
            ->expects($this->once())
            ->method('sendConfirmationEmail')
            ->with(
                $command->email(),
                $command->name(),
                $this->isType('string'), // userId
                $this->isType('string')  // confirmationToken
            );

        // Si el comando no tiene residentUnitId, el repositorio no debería ser llamado para buscar
        $this->residentUnitRepository->expects($this->never())->method('findOneByIdOrFail');

        // 3. Invoke the handler
        ($this->handler)($command);
    }

    public function test_it_should_register_a_user_with_resident_unit_id_and_send_confirmation_email(): void
    {
        // 1. Prepare test data
        $residentUnitId = 'some-resident-unit-id';
        $command = $this->createRegisterUserCommand($residentUnitId);
        $residentUnit = $this->createMock(ResidentUnit::class);

        // 2. Set mock expectations
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($command->email())
            ->willReturn(null);

        $this->residentUnitRepository
            ->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($residentUnitId)
            ->willReturn($residentUnit);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class));

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->userMailer
            ->expects($this->once())
            ->method('sendConfirmationEmail')
            ->with(
                $command->email(),
                $command->name(),
                $this->isType('string'), // userId
                $this->isType('string')  // confirmationToken
            );

        // 3. Invoke the handler
        ($this->handler)($command);
    }

    public function test_it_should_throw_exception_when_user_already_exists(): void
    {
        // 1. Expect the exception
        $this->expectException(ResourceAlreadyExistException::class);

        // 2. Prepare test data
        $command = $this->createRegisterUserCommand();
        $existingUser = $this->createMock(User::class);

        // 3. Set mock expectations
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($command->email())
            ->willReturn($existingUser);

        $this->userRepository->expects($this->never())->method('save');
        $this->userMailer->expects($this->never())->method('sendConfirmationEmail');
        $this->residentUnitRepository->expects($this->never())->method('findOneByIdOrFail'); // No debería buscar ResidentUnit si el usuario ya existe

        // 4. Invoke the handler
        ($this->handler)($command);
    }

    public function test_it_should_throw_exception_when_resident_unit_not_found(): void
    {
        // 1. Expect the exception
        $this->expectException(ResourceNotFoundException::class);

        // 2. Prepare test data
        $residentUnitId = 'non-existent-resident-unit-id';
        $command = $this->createRegisterUserCommand($residentUnitId);

        // 3. Set mock expectations
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($command->email())
            ->willReturn(null);

        $this->residentUnitRepository
            ->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($residentUnitId)
            ->willThrowException(ResourceNotFoundException::createFromClassAndId(ResidentUnit::class, $residentUnitId));

        $this->userRepository->expects($this->never())->method('save');
        $this->userMailer->expects($this->never())->method('sendConfirmationEmail');

        // 4. Invoke the handler
        ($this->handler)($command);
    }

    public function test_it_should_still_register_user_when_email_fails_to_send(): void
    {
        // 1. Prepare test data
        $command = $this->createRegisterUserCommand();

        // 2. Set mock expectations
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($command->email())
            ->willReturn(null);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class));

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->userMailer
            ->expects($this->once())
            ->method('sendConfirmationEmail')
            ->willThrowException(new Exception('Email service is down'));

        $this->residentUnitRepository->expects($this->never())->method('findOneByIdOrFail'); // No debería buscar ResidentUnit si el comando no tiene residentUnitId

        try {
            ($this->handler)($command);
        } catch (Exception $e) {
            $this->assertSame('Email service is down', $e->getMessage());
        }
    }

    private function createRegisterUserCommand(?string $residentUnitId = null): RegisterUserCommand
    {
        return new RegisterUserCommand(
            UserIdMother::create()->value(),
            UserNameMother::create()->value(),
            EmailMother::create()->value(),
            PasswordMother::create()->value(),
            $residentUnitId // Añadido: Pasar residentUnitId al comando
        );
    }
}
