<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Controller;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[OA\Get(
    path: '/api/v1/resident-unit/health-check',
    summary: 'Resident units context health check',
    tags: ['Resident Units'],
    security: [],
    responses: [
        new OA\Response(response: 200, description: 'Service is healthy.'),
    ],
)]
class HealthCheckController
{
    public function __invoke(): Response
    {
        return new JsonResponse([
            'status' => Response::HTTP_OK,
        ]);
    }
}
