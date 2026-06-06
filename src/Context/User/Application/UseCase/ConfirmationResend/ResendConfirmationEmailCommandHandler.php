<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\ConfirmationResend;

use App\Context\User\Application\Service\UserMailerInterface;
use App\Context\User\Domain\UserRepository;
use App\Shared\Application\CommandHandler;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

final readonly class ResendConfirmationEmailCommandHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private UserMailerInterface $userMailer,
    ) {}

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(ResendConfirmationEmailCommand $command): void
    {
        $user = $this->userRepository->findByEmail($command->email());

        // No revelar si el email existe o si la cuenta ya está activa (evitar enumeración).
        if (null === $user || true === $user->isActive()) {
            return;
        }

        $user->refreshConfirmationToken();
        $this->userRepository->save($user, true);

        $this->userMailer->sendConfirmationEmail(
            $user->getEmail(),
            $user->getName(),
            $user->getId(),
            $user->getConfirmationToken(),
        );
    }
}
