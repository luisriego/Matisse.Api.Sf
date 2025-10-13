<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\Activation\ActivateUserCommand;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;

final class ActivateUserController extends ApiController
{
    /**
     * @throws Throwable
     */
    public function __invoke(string $userId, string $token): JsonResponse
    {
        try {
            $this->dispatch(new ActivateUserCommand($userId, $token));
        } catch (HandlerFailedException $e) {
            throw $this->unwrap($e);
        }

        return new JsonResponse(
            ['message' => 'Tu cuenta ha sido activada correctamente. Ya puedes iniciar sesión.'],
            Response::HTTP_OK,
        );
    }

    protected function exceptions(): array
    {
        return [
            ResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
        ];
    }

    private function unwrap(Throwable $e): Throwable
    {
        $previous = $e->getPrevious();

        if ($previous instanceof HandlerFailedException) {
            return $this->unwrap($previous);
        }

        return $previous ?? $e;
    }
}
