<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\Activation\ActivateUserCommand;
use App\Context\User\Application\UseCase\Activation\ActivateUserCommandHandler;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class ActivateUserController
{
    public function __construct(
        private ActivateUserCommandHandler $commandHandler,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(string $userId, string $token): JsonResponse
    {
        try {
            ($this->commandHandler)(new ActivateUserCommand($userId, $token));
        } catch (ResourceNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(
            ['message' => 'Tu cuenta ha sido activada correctamente. Ya puedes iniciar sesión.'],
            Response::HTTP_OK,
        );
    }
}
