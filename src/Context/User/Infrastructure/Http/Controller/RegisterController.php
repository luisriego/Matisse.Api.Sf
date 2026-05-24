<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\Registration\RegisterUserCommand;
use App\Context\User\Application\UseCase\Registration\RegisterUserCommandHandler;
use App\Context\User\Infrastructure\Http\Dto\RegisterUserRequestDto;
use App\Shared\Domain\Exception\ResourceAlreadyExistException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[OA\Post(
    path: '/api/v1/users/register',
    summary: 'Register a new user',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['id', 'name', 'email', 'password'],
            properties: [
                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
                new OA\Property(property: 'residentUnitId', type: 'string', format: 'uuid', nullable: true),
            ],
        ),
    ),
    tags: ['Users'],
    security: [],
    responses: [
        new OA\Response(response: 201, description: 'User registered successfully.'),
        new OA\Response(response: 409, description: 'User already exists.'),
    ],
)]
final readonly class RegisterController
{
    public function __construct(
        private RegisterUserCommandHandler $commandHandler,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(RegisterUserRequestDto $requestDto): JsonResponse
    {
        try {
            ($this->commandHandler)(new RegisterUserCommand(
                $requestDto->id(),
                $requestDto->name(),
                $requestDto->email(),
                $requestDto->password(),
                $requestDto->residentUnitId(),
            ));
        } catch (ResourceAlreadyExistException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse(['message' => 'User registered successfully.'], Response::HTTP_CREATED);
    }
}
