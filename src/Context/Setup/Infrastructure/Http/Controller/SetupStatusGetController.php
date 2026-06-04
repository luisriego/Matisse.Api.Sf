<?php

declare(strict_types=1);

namespace App\Context\Setup\Infrastructure\Http\Controller;

use App\Context\Setup\Application\Service\CatalogSeeder;
use App\Context\Setup\Application\UseCase\GetSetupStatus\GetSetupStatusQuery;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

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
    public function __construct(
        MessageBusInterface $commandBus,
        MessageBusInterface $queryBus,
        private readonly CatalogSeeder $catalogSeeder,
    ) {
        parent::__construct($commandBus, $queryBus);
    }

    public function __invoke(): JsonResponse
    {
        // Loading the wizard (after login) ensures the reference catalogs exist. Idempotent.
        $this->catalogSeeder->ensureSeeded();

        return new JsonResponse($this->ask(new GetSetupStatusQuery()), Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [];
    }
}
