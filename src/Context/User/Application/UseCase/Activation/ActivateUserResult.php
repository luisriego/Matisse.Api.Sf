<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\Activation;

final readonly class ActivateUserResult
{
    public function __construct(public string $redirectUrl) {}
}
