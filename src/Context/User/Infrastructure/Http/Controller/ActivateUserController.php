<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\Activation\ActivateUserCommand;
use App\Context\User\Application\UseCase\Activation\ActivateUserCommandHandler;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[OA\Get(
    path: '/api/v1/users/activate/{userId}/{token}',
    summary: 'Activate user account via email token',
    tags: ['Users'],
    security: [],
    parameters: [
        new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Account activated.'),
        new OA\Response(response: 400, description: 'Invalid token.'),
        new OA\Response(response: 404, description: 'User not found.'),
    ],
)]
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
