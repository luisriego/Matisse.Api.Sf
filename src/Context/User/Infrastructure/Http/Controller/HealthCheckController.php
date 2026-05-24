<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;

#[OA\Get(
    path: '/api/v1/users/health-check',
    summary: 'Users context health check',
    tags: ['Users'],
    security: [],
    responses: [
        new OA\Response(response: 200, description: 'Service is healthy.'),
    ],
)]
final class HealthCheckController
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'status' => JsonResponse::HTTP_OK,
        ]);
    }
}
