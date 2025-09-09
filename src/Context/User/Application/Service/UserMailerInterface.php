<?php

declare(strict_types=1);

namespace App\Context\User\Application\Service;

interface UserMailerInterface
{
    public function sendWelcomeEmail(string $userEmail, string $userName): void;
}
