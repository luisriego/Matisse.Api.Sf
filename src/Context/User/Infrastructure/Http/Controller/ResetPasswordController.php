<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\PasswordReset\ResetPasswordCommand;
use App\Context\User\Application\UseCase\PasswordReset\ResetPasswordCommandHandler;
use App\Context\User\Infrastructure\Http\Dto\ResetPasswordRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[OA\Post(
    path: '/api/v1/users/{userId}/password-reset/{token}',
    summary: 'Reset password using email token',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['newPassword'],
            properties: [
                new OA\Property(property: 'newPassword', type: 'string', format: 'password'),
            ],
        ),
    ),
    tags: ['Users'],
    security: [],
    parameters: [
        new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Password reset successfully.'),
        new OA\Response(response: 400, description: 'Invalid token or password.'),
        new OA\Response(response: 404, description: 'User not found.'),
    ],
)]
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
