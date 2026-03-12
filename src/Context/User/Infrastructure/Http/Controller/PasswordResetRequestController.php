<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\PasswordReset\PasswordResetRequestCommand;
use App\Context\User\Application\UseCase\PasswordReset\PasswordResetRequestCommandHandler;
use App\Context\User\Infrastructure\Http\Dto\PasswordResetRequestDto;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class PasswordResetRequestController
{
    public function __construct(
        private PasswordResetRequestCommandHandler $commandHandler,
    ) {}

    public function __invoke(PasswordResetRequestDto $requestDto): JsonResponse
    {
        // Intentionally returns success even if the email doesn't exist to prevent user enumeration.
        ($this->commandHandler)(new PasswordResetRequestCommand($requestDto->email()));

        return new JsonResponse(
            ['message' => 'Se o seu endereço de e-mail estiver registrado, você receberá um link para redefinir sua senha.'],
            Response::HTTP_OK,
        );
    }
}
