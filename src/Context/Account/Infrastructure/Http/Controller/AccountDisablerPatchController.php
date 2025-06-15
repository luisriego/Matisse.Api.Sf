<?php

namespace App\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Application\UseCase\DisableAccount\DisableAccountCommandHandler;
use App\Context\Account\Application\UseCase\DisableAccount\DisableAccountCommand;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class AccountDisablerPatchController
{
    public function __construct(private DisableAccountCommandHandler $commandHandler) {}

    #[Route("/disable/{id}", name: "account_disable", methods: ["PATCH"])]
    public function __invoke(string $id): Response
    {
        $command = new DisableAccountCommand($id);

        $this->commandHandler->__invoke($command);

        return new Response("", Response::HTTP_NO_CONTENT);
    }
}