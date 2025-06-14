<?php

namespace App\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Application\UseCase\CreateAccount\CreateAccountCommandHandler;
use App\Context\Account\Application\UseCase\CreateAccount\CreateAccountRequestDto;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class AccountCreatorPutController
{
    public function __construct(
        private CreateAccountCommand $accountCommand,
    ) {
    }

    #[Route('/create', name: 'account_create', methods: ['PUT'])]
    public function __invoke(CreateAccountRequestDto $requestDto): Response
    {
        $command = new CreateAccountCommand(
            $requestDto->id,
            $requestDto->code,
            $requestDto->name,
        );

        $accountCommand->

        return new Response('', Response::HTTP_CREATED);
    }
}