<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Application\UseCase\EnableAccount\EnableAccountCommand;
use App\Context\Account\Application\UseCase\EnableAccount\EnableAccountCommandHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class AccountEnablerPatchController
{
    public function __construct(private EnableAccountCommandHandler $commandHandler) {}

    #[Route('/enable/{id}', name: 'account_enable', methods: ['PATCH'])]
    public function __invoke(string $id): Response
    {
        $command = new EnableAccountCommand($id);

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
