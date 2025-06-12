<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Http\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/health-check', name: 'account_health_check', methods: ['GET'])]
final class HealthCheckController
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'status' => JsonResponse::HTTP_OK,
        ]);
    }
}
