<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\ResidentUnit\UnlinkResidentUnitFromUserCommand;
use App\Context\User\Application\UseCase\ResidentUnit\UnlinkResidentUnitFromUserCommandHandler;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Delete(
    path: '/api/v1/users/{id}/resident-unit',
    summary: 'Unlink resident unit from user',
    tags: ['Users'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 204, description: 'Resident unit unlinked.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final readonly class UnlinkResidentUnitFromUserDeleteController
{
    public function __construct(private UnlinkResidentUnitFromUserCommandHandler $handler) {}

    public function __invoke(string $id): Response
    {
        $this->handler->__invoke(new UnlinkResidentUnitFromUserCommand($id));

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
