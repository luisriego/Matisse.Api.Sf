<?php

declare(strict_types=1);

namespace App\Context\User\Application\Service;

interface UserMailerInterface
{
    public function sendConfirmationEmail(string $userEmail, string $userName, string $userId, string $confirmationToken): void;

    public function sendPasswordResetEmail(string $userEmail, string $userName, string $userId, string $passwordResetToken): void;
}
