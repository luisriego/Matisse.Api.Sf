<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\FindUser\FindUserQuery;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

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
