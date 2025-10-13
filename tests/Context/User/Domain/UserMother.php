<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Domain;

use App\Context\User\Domain\User;
use App\Context\User\Domain\ValueObject\Email;
use App\Context\User\Domain\ValueObject\Password;
use App\Context\User\Domain\ValueObject\UserId;
use App\Context\User\Domain\ValueObject\UserName;
use App\Tests\Context\User\Domain\ValueObject\EmailMother;
use App\Tests\Context\User\Domain\ValueObject\PasswordMother;
use App\Tests\Context\User\Domain\ValueObject\UserIdMother;
use App\Tests\Context\User\Domain\ValueObject\UserNameMother;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

final class UserMother
{
    public static function createRandom(
        ?UserId $id = null,
        ?UserName $name = null,
        ?Email $email = null,
        ?Password $password = null,
        ?UserPasswordHasherInterface $passwordHasher = null
    ): User {
        // A mock hasher is needed for User::create. If not provided, create a simple one.
        $hasher = $passwordHasher ?? new class implements UserPasswordHasherInterface {
            public function hashPassword(PasswordAuthenticatedUserInterface $user, string $plainPassword): string
            {
                return 'hashed_password_for_' . $plainPassword;
            }

            public function isPasswordValid(PasswordAuthenticatedUserInterface $user, string $plainPassword): bool
            {
                return true;
            }

            public function needsRehash(PasswordAuthenticatedUserInterface $user): bool
            {
                return false;
            }
        };

        return User::create(
            $id ?? UserIdMother::create(),
            $name ?? UserNameMother::create(),
            $email ?? EmailMother::create(),
            $password ?? PasswordMother::create(),
            $hasher
        );
    }
}
