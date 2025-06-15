<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Application\UseCase\CreateAccount\CreateAccountCommand;
use App\Context\Account\Application\UseCase\CreateAccount\CreateAccountCommandHandler;
use App\Context\Account\Infrastructure\Http\Dto\CreateAccountRequestDto;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class AccountCreatorPutController
{
    public function __construct(
        private CreateAccountCommandHandler $commandHandler,
    ) {}

    #[Route('/create', name: 'account_create', methods: ['PUT'])]
    public function __invoke(CreateAccountRequestDto $requestDto): Response
    {
        $command = new CreateAccountCommand(
            $requestDto->id,
            $requestDto->code,
            $requestDto->name,
        );

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_CREATED);
    }
}
