<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\PasswordReset;

use App\Context\User\Application\Service\UserMailerInterface;
use App\Context\User\Domain\UserRepository;
use App\Shared\Application\CommandHandler;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

final class PasswordResetRequestCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserMailerInterface $userMailer
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(PasswordResetRequestCommand $command): void
    {
        $user = $this->userRepository->findByEmail($command->email());

        // We intentionally do not throw an exception if the user is not found
        // to prevent user enumeration.
        if (null === $user) {
            return;
        }

        // Generate and save the password reset token
        $user->generatePasswordResetToken();
        $this->userRepository->save($user, true);

        // Send the password reset email
        $this->userMailer->sendPasswordResetEmail(
            $user->getEmail(),
            $user->getName(),
            $user->getId(),
            $user->getPasswordResetToken()
        );
    }
}
