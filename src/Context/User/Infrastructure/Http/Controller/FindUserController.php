<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\FindUser\FindUserQuery;
use App\Context\User\Domain\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface; // Importar HandledStamp
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class FindUserController extends AbstractController
{
    public function __construct(private readonly MessageBusInterface $queryBus) {}

    public function __invoke(string $id): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new FindUserQuery($id));

        /** @var HandledStamp $stamp */
        $stamp = $envelope->last(HandledStamp::class);

        /** @var User|null $user */
        $user = $stamp->getResult();

        if (null === $user) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($user->toArray(), Response::HTTP_OK);
    }
}
