<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Domain\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Throwable;

#[OA\Post(
    path: '/api/v1/login_check',
    summary: 'Authenticate and obtain JWT token',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
            ],
        ),
    ),
    tags: ['Authentication'],
    security: [],
    responses: [
        new OA\Response(response: 200, description: 'Login successful; returns JWT token.'),
        new OA\Response(response: 401, description: 'Invalid credentials.'),
    ],
)]
final readonly class LoginController
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(#[CurrentUser] ?UserInterface $user): JsonResponse
    {
        if (null === $user) {
            return new JsonResponse(['message' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Invalid user type.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $token = $this->jwtManager->create($user);

        return new JsonResponse([
            'message' => 'Login successful.',
            'token'   => $token,
        ]);
    }
}
