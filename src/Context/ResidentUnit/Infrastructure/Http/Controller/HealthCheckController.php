<?php

namespace App\Context\ResidentUnit\Infrastructure\Http\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class HealthCheckController
{
    public function __invoke(): Response
    {
        return new JsonResponse([
            'status' => Response::HTTP_OK,
        ]);
    }
}