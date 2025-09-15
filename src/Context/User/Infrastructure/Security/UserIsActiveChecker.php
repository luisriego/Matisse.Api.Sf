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
                'Sua conta não foi ativada. Por favor, verifique seu e-mail para o link de confirmação.',
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // No necesitamos hacer nada después de la autenticación.
    }
}
