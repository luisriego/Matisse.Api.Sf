<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\PasswordReset;

use App\Context\User\Domain\UserRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Clock;
use App\Shared\Domain\Exception\InvalidArgumentException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ResetPasswordCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly Clock $clock, // Inyectar Clock
        private readonly int $passwordResetTokenLifetime, // Inyectar vida útil del token
    ) {}

    public function __invoke(ResetPasswordCommand $command): void
    {
        $user = $this->userRepository->findOneByIdOrFail($command->userId());

        if (null === $user->getPasswordResetToken() || null === $user->getPasswordResetRequestedAt()) {
            throw new InvalidArgumentException('Token de redefinição de senha inválido ou expirado.');
        }

        if ($user->getPasswordResetToken() !== $command->token()) {
            throw new InvalidArgumentException('Token de redefinição de senha inválido.');
        }

        // Comprobar la expiración del token
        $expiresAt = $user->getPasswordResetRequestedAt()->getTimestamp() + $this->passwordResetTokenLifetime;
        $now = $this->clock->now()->getTimestamp();

        if ($now > $expiresAt) {
            throw new InvalidArgumentException('Token de redefinição de senha expirado.');
        }

        // Redefinir la contraseña del usuario
        $user->changePassword($command->newPassword(), $this->passwordHasher);
        $user->clearPasswordResetToken(); // Limpiar el token después de su uso

        $this->userRepository->save($user, true);
    }
}
