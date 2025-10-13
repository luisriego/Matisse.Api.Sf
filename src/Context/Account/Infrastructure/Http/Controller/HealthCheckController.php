<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Http\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/health-check', name: 'account_health_check', methods: ['GET'], priority: 10)]
final class HealthCheckController
{
    public function __invoke(): Response
    {
        return new JsonResponse([
            'status' => Response::HTTP_OK,
        ]);
    }
}
