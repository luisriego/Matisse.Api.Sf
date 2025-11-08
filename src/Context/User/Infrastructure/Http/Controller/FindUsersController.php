<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\FindUsers\FindUsersQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Serializer\SerializerInterface;

final class FindUsersController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $queryBus,
        private readonly SerializerInterface $serializer,
    ) {}

    public function __invoke(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new FindUsersQuery());

        /** @var HandledStamp $stamp */
        $stamp = $envelope->last(HandledStamp::class);

        /** @var array|null $users */
        $users = $stamp->getResult();

        $usersAsArray = $this->serializer->normalize($users);

        return new JsonResponse($usersAsArray, Response::HTTP_OK);
    }
}
