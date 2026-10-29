<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Domain\User;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Get(
    path: '/api/v1/users/me',
    summary: 'Get authenticated user profile',
    tags: ['Users'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'User profile.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final readonly class MeUserGetController
{
    public function __invoke(#[CurrentUser] ?User $user): JsonResponse
    {
        return new JsonResponse([
            'id' => $user->id(),
            'email' => $user->getEmail(),
            'name' => $user->name(),
            'lastName' => $user->lastName(),
            'roles' => $user->getRoles(),
            'isActive' => $user->isActive(),
            'residentUnitId' => $user->getResidentUnit()?->id(),
        ], Response::HTTP_OK);
    }
}
