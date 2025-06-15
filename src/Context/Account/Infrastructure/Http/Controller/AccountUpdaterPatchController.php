<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Application\UseCase\UpdateAccount\UpdateAccountCommand;
use App\Context\Account\Application\UseCase\UpdateAccount\UpdateAccountCommandHandler;
use App\Context\Account\Infrastructure\Http\Dto\UpdateAccountRequestDto;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class AccountUpdaterPatchController
{
    public function __construct(private UpdateAccountCommandHandler $commandHandler) {}

    #[Route('/update/{id}', name: 'account_edit', methods: ['PATCH'])]
    public function __invoke(string $id, UpdateAccountRequestDto $requestDto): Response
    {
        $command = new UpdateAccountCommand(
            $id,
            $requestDto->code,
            $requestDto->name,
        );

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
