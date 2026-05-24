<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Http\Controller;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[OA\Get(
    path: '/api/v1/accounts/health-check',
    summary: 'Accounts context health check',
    tags: ['Accounts'],
    security: [],
    responses: [
        new OA\Response(response: 200, description: 'Service is healthy.'),
    ],
)]
final class HealthCheckController
{
    public function __invoke(): Response
    {
        return new JsonResponse([
            'status' => Response::HTTP_OK,
        ]);
    }
}
