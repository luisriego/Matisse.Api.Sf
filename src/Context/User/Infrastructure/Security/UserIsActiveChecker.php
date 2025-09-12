<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Security;

use App\Context\User\Domain\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserIsActiveChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAuthenticationException(
                'Tu cuenta no ha sido activada. Por favor, revisa tu email para el enlace de confirmación.',
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // No necesitamos hacer nada después de la autenticación.
    }
}
