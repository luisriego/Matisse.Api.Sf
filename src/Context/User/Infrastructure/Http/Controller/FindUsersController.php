<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\FindUsers\FindUsersQuery;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Get(
    path: '/api/v1/users',
    summary: 'List all users',
    tags: ['Users'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'List of users.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class FindUsersController extends ApiController
{
    public function __construct(
        MessageBusInterface $commandBus,
        MessageBusInterface $queryBus,
        private readonly NormalizerInterface $normalizer,
    ) {
        parent::__construct($commandBus, $queryBus);
    }

    public function __invoke(): JsonResponse
    {
        $users = $this->ask(new FindUsersQuery());
        $data = $this->normalizer->normalize($users);

        return new JsonResponse($data ?? [], Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [];
    }
}
