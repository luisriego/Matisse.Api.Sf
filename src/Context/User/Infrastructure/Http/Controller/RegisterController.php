<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\Registration\RegisterUserCommand;
use App\Context\User\Infrastructure\Http\Dto\RegisterUserRequestDto;
use App\Shared\Domain\Exception\ResourceAlreadyExistException;
use App\Shared\Infrastructure\Symfony\ApiController;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

final class RegisterController extends ApiController
{
    public function __construct(
        MessageBusInterface $commandBus,
        MessageBusInterface $queryBus,
    ) {
        parent::__construct($commandBus, $queryBus);
    }

    /**
     * @throws Throwable
     */
    public function __invoke(RegisterUserRequestDto $requestDto): JsonResponse
    {
        try {
            $command = new RegisterUserCommand(
                $requestDto->id(),
                $requestDto->name(),
                $requestDto->email(),
                $requestDto->password()
            );
            $this->dispatch($command);

            return new JsonResponse(['message' => 'User registered successfully.'], Response::HTTP_CREATED);
        } catch (HandlerFailedException $e) {
            $root = $this->unwrap($e);

            if ($root instanceof ResourceAlreadyExistException) {
                return new JsonResponse(['error' => $root->getMessage()], Response::HTTP_CONFLICT);
            }

            throw $root;
        }
    }

    protected function unwrap(Throwable $e): Throwable
    {
        $t = $e;

        while ($t instanceof HandlerFailedException && $t->getPrevious() !== null) {
            $t = $t->getPrevious();
        }

        return $t;
    }

    protected function exceptions(): array
    {
        return [
            BadRequestHttpException::class => Response::HTTP_BAD_REQUEST,
            ResourceAlreadyExistException::class => Response::HTTP_CONFLICT,
            JsonException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
