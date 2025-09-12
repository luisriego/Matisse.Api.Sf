<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\Registration;

use App\Context\User\Application\Service\UserMailerInterface;
use App\Context\User\Domain\User;
use App\Context\User\Domain\UserRepository;
use App\Context\User\Domain\ValueObject\Email;
use App\Context\User\Domain\ValueObject\Password;
use App\Context\User\Domain\ValueObject\UserId;
use App\Context\User\Domain\ValueObject\UserName;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Exception\ResourceAlreadyExistException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use function sprintf;

final class RegisterUserCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly UserMailerInterface $userMailer,
    ) {}

    /**
     * @throws ResourceAlreadyExistException
     */
    public function __invoke(RegisterUserCommand $command): void
    {
        $email = Email::fromString($command->email());

        if (null !== $this->userRepository->findByEmail($email->value())) {
            throw new ResourceAlreadyExistException(sprintf('User with email <%s> already exists.', $email->value()));
        }

        $user = User::create(
            UserId::fromString($command->id()),
            UserName::fromString($command->name()),
            Email::fromString($command->email()),
            Password::fromString($command->password()),
            $this->userPasswordHasher,
        );

        $this->userRepository->save($user, true);

        // Enviar el email de confirmación
        $this->userMailer->sendConfirmationEmail(
            $user->getEmail(),
            $user->getName(),
            $user->getId(),
            $user->getConfirmationToken(),
        );
    }
}
