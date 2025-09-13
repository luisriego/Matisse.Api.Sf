<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\PasswordReset\PasswordResetRequestCommand;
use App\Context\User\Infrastructure\Http\Dto\PasswordResetRequestDto;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class PasswordResetRequestController extends ApiController
{
    /**
     * @throws Throwable
     */
    public function __invoke(PasswordResetRequestDto $requestDto): JsonResponse
    {
        // Dispatch the command to handle the password reset request
        // We intentionally return a success message even if the email doesn't exist
        // to prevent user enumeration.
        $this->dispatch(new PasswordResetRequestCommand($requestDto->email()));

        return new JsonResponse(
            ['message' => 'Se o seu endereço de e-mail estiver registrado, você receberá um link para redefinir sua senha.'], // <--- Mensaje en pt-br
            Response::HTTP_OK
        );
    }

    protected function exceptions(): array
    {
        // No necesitamos mapear excepciones aquí, ya que el controlador siempre devuelve 200 OK
        // para evitar la enumeración de usuarios, incluso si el email no existe.
        return [];
    }
}
