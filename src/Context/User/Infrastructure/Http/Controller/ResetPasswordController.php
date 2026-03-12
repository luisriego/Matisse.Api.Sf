<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\PasswordReset\ResetPasswordCommand;
use App\Context\User\Application\UseCase\PasswordReset\ResetPasswordCommandHandler;
use App\Context\User\Infrastructure\Http\Dto\ResetPasswordRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class ResetPasswordController
{
    public function __construct(
        private ResetPasswordCommandHandler $commandHandler,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(string $userId, string $token, ResetPasswordRequestDto $requestDto): JsonResponse
    {
        try {
            ($this->commandHandler)(new ResetPasswordCommand(
                $userId,
                $token,
                $requestDto->newPassword(),
            ));
        } catch (ResourceNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(
            ['message' => 'Sua senha foi redefinida com sucesso. Você já pode fazer login.'],
            Response::HTTP_OK,
        );
    }
}
