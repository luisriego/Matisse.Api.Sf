<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\ChangePassword\ChangePasswordCommand;
use App\Context\User\Infrastructure\Http\Dto\ChangePasswordRequestDto;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Throwable;

final class ChangePasswordController extends ApiController
{
    /**
     * @throws Throwable
     */
    public function __invoke(ChangePasswordRequestDto $requestDto, #[CurrentUser] ?UserInterface $user): JsonResponse
    {
        if (null === $user) {
            return new JsonResponse(['message' => 'User not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $this->dispatch(new ChangePasswordCommand(
                $user->getUserIdentifier(), // The email
                $requestDto->oldPassword(),
                $requestDto->newPassword(),
            ));
        } catch (HandlerFailedException $e) {
            throw $this->unwrap($e);
        }

        return new JsonResponse(['message' => 'Password changed successfully.'], Response::HTTP_OK);
    }

    protected function exceptions(): array
    {
        return [
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
