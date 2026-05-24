<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Application\UseCase\UpdateAccount\UpdateAccountCommand;
use App\Context\Account\Application\UseCase\UpdateAccount\UpdateAccountCommandHandler;
use App\Context\Account\Infrastructure\Http\Dto\UpdateAccountRequestDto;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Patch(
    path: '/api/v1/accounts/{id}',
    summary: 'Update account name and description',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'description', type: 'string'),
            ],
        ),
    ),
    tags: ['Accounts'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 204, description: 'Account updated.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final readonly class AccountUpdaterPatchController
{
    public function __construct(private UpdateAccountCommandHandler $commandHandler) {}

    public function __invoke(string $id, UpdateAccountRequestDto $requestDto): Response
    {
        $command = new UpdateAccountCommand(
            $id,
            $requestDto->name,
            $requestDto->description,
        );

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
