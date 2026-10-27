<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Controller;

use App\Context\Setup\Application\Service\SetupStatusChecker;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route(
    path: '/api/health',
    methods: ['GET'],
)]
#[OA\Get(
    path: '/api/health',
    summary: 'Platform health check with dependency status',
    tags: ['Platform'],
    security: [],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Platform is healthy.',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string'),
                    new OA\Property(property: 'setup', type: 'object'),
                ],
                type: 'object',
            ),
        ),
        new OA\Response(
            response: 503,
            description: 'Service unavailable — one or more dependencies are unhealthy.',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string'),
                    new OA\Property(property: 'dependencies', type: 'object'),
                ],
                type: 'object',
            ),
        ),
    ],
)]
final class HealthCheckController
{
    public function __construct(
        private readonly SetupStatusChecker $setupStatusChecker,
    ) {}

    public function __invoke(): JsonResponse
    {
        $dependencies = [
            'setup' => $this->checkSetup(),
        ];

        $allHealthy = true;

        foreach ($dependencies as $service => $result) {
            if ($result['status'] === 'unhealthy') {
                $allHealthy = false;
                break;
            }
        }

        if ($allHealthy) {
            return new JsonResponse([
                'status'       => 'healthy',
                'dependencies' => $dependencies,
            ], Response::HTTP_OK);
        }

        return new JsonResponse([
            'status'       => 'unhealthy',
            'dependencies' => $dependencies,
        ], Response::HTTP_SERVICE_UNAVAILABLE);
    }

    /**
     * @return array{status: string, message?: string}
     */
    private function checkSetup(): array
    {
        try {
            $status = $this->setupStatusChecker->status();

            if ($status['complete'] || $status['fullyOnboarded']) {
                return ['status' => 'healthy'];
            }

            return [
                'status'  => 'degraded',
                'message' => $status['message'] ?? 'Setup incompleto',
            ];
        } catch (Throwable $e) {
            return [
                'status'  => 'unhealthy',
                'message' => $e->getMessage(),
            ];
        }
    }
}
