<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\ChangePassword\ChangePasswordCommand;
use App\Context\User\Application\UseCase\ChangePassword\ChangePasswordCommandHandler;
use App\Context\User\Infrastructure\Http\Dto\ChangePasswordRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Throwable;

#[OA\Patch(
    path: '/api/v1/users/change-password',
    summary: 'Change password for authenticated user',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['oldPassword', 'newPassword'],
            properties: [
                new OA\Property(property: 'oldPassword', type: 'string', format: 'password'),
                new OA\Property(property: 'newPassword', type: 'string', format: 'password'),
            ],
        ),
    ),
    tags: ['Users'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Password changed successfully.'),
        new OA\Response(response: 400, description: 'Validation error.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final readonly class ChangePasswordController
{
    public function __construct(
        private ChangePasswordCommandHandler $commandHandler,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(ChangePasswordRequestDto $requestDto, #[CurrentUser] ?UserInterface $user): JsonResponse
    {
        if (null === $user) {
            return new JsonResponse(['message' => 'User not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            ($this->commandHandler)(new ChangePasswordCommand(
                $user->getUserIdentifier(),
                $requestDto->oldPassword(),
                $requestDto->newPassword(),
            ));
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['message' => 'Password changed successfully.'], Response::HTTP_OK);
    }
}
