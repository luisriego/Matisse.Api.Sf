<?php

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\Update\UpdateUserCommand;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class UpdateUserController extends AbstractController
{
    public function __construct(private readonly MessageBusInterface $commandBus)
    {
    }

    public function __invoke(Request $request, string $id): Response
    {
        $data = $request->toArray();

        $command = new UpdateUserCommand(
            $id,
            $data['name'],
            $data['lastName'],
            $data['gender'],
            $data['phoneNumber']
        );

        $this->commandBus->dispatch($command);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
