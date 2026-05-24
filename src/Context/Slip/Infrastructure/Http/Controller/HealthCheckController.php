<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Controller;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[OA\Get(
    path: '/api/v1/slips/health-check',
    summary: 'Slips context health check',
    tags: ['Slips'],
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
