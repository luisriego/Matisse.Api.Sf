<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Application\UseCase\EnableAccount\EnableAccountCommand;
use App\Context\Account\Application\UseCase\EnableAccount\EnableAccountCommandHandler;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Patch(
    path: '/api/v1/accounts/enable/{id}',
    summary: 'Enable account',
    tags: ['Accounts'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 204, description: 'Account enabled.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final readonly class AccountEnablerPatchController
{
    public function __construct(private EnableAccountCommandHandler $commandHandler) {}

    public function __invoke(string $id): Response
    {
        $command = new EnableAccountCommand($id);

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
