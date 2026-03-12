<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\Registration\RegisterUserCommand;
use App\Context\User\Application\UseCase\Registration\RegisterUserCommandHandler;
use App\Context\User\Infrastructure\Http\Dto\RegisterUserRequestDto;
use App\Shared\Domain\Exception\ResourceAlreadyExistException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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
