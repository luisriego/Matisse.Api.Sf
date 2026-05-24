<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\FindUser\FindUserQuery;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Get(
    path: '/api/v1/users/{id}',
    summary: 'Get user by ID',
    tags: ['Users'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'User details.'),
        new OA\Response(response: 404, description: 'User not found.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class FindUserController extends ApiController
{
    public function __construct(
        MessageBusInterface $commandBus,
        MessageBusInterface $queryBus,
        private readonly NormalizerInterface $normalizer,
    ) {
        parent::__construct($commandBus, $queryBus);
    }

    public function __invoke(string $id): JsonResponse
    {
        $user = $this->ask(new FindUserQuery($id));

        if (null === $user) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $data = $this->normalizer->normalize($user);

        return new JsonResponse($data, Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [];
    }
}
