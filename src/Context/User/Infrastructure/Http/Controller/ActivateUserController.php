<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\Activation\ActivateUserCommand;
use App\Context\User\Application\UseCase\Activation\ActivateUserCommandHandler;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function rtrim;
use function sprintf;
use function trim;

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
        new OA\Response(response: 302, description: 'Redirects to sign-in or set-password page.'),
        new OA\Response(response: 400, description: 'Invalid token.'),
        new OA\Response(response: 404, description: 'User not found.'),
    ],
)]
final readonly class ActivateUserController
{
    public function __construct(
        private ActivateUserCommandHandler $commandHandler,
        private string $appBaseUrl,
        private string $frontSignInPath,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(string $userId, string $token): Response
    {
        try {
            $result = ($this->commandHandler)(new ActivateUserCommand($userId, $token));

            return new RedirectResponse($result->redirectUrl);
        } catch (InvalidArgumentException|ResourceNotFoundException) {
            return new RedirectResponse($this->signInUrlWithError('activation_failed'));
        }
    }

    private function signInUrlWithError(string $error): string
    {
        return sprintf(
            '%s%s?error=%s',
            rtrim($this->appBaseUrl, '/'),
            '/' . trim($this->frontSignInPath, '/'),
            $error,
        );
    }
}
