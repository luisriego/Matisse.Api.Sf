<?php

declare(strict_types=1);

namespace App\Context\Setup\Infrastructure\Http\Controller;

use App\Context\Setup\Application\UseCase\GetSetupStatus\GetSetupStatusQuery;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[OA\Get(
    path: '/api/v1/setup/status',
    summary: 'Get condominium setup wizard status',
    tags: ['Setup'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Setup status.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class SetupStatusGetController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->ask(new GetSetupStatusQuery()), Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [];
    }
}
