<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Application\UseCase\CreateAccount\CreateAccountCommand;
use App\Context\Account\Application\UseCase\CreateAccount\CreateAccountCommandHandler;
use App\Context\Account\Infrastructure\Http\Dto\CreateAccountRequestDto;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Put(
    path: '/api/v1/accounts/create',
    summary: 'Create account and opening balance',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['id', 'name', 'initialBalanceAmount', 'initialBalanceDate'],
            properties: [
                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'name', type: 'string', example: 'Conta Principal'),
                new OA\Property(
                    property: 'initialBalanceAmount',
                    type: 'integer',
                    example: 250000,
                    description: 'Opening balance in cents (same as SetInitialBalance).',
                ),
                new OA\Property(
                    property: 'initialBalanceDate',
                    type: 'string',
                    format: 'date',
                    example: '2026-01-05',
                    description: 'Effective date of the opening balance (Y-m-d).',
                ),
            ],
        ),
    ),
    tags: ['Accounts'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 201, description: 'Account created; InitialBalanceSet event recorded.'),
        new OA\Response(response: 400, description: 'Validation error (e.g. missing opening balance fields).'),
    ],
)]
final readonly class AccountCreatorPutController
{
    public function __construct(
        private CreateAccountCommandHandler $commandHandler,
    ) {}

    public function __invoke(CreateAccountRequestDto $requestDto): Response
    {
        $command = new CreateAccountCommand(
            $requestDto->id,
            $requestDto->name,
            $requestDto->initialBalanceAmount,
            $requestDto->initialBalanceDate,
        );

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_CREATED);
    }
}
