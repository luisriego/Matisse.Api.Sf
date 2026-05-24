<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\ResidentUnit\LinkResidentUnitToUserCommand;
use App\Context\User\Application\UseCase\ResidentUnit\LinkResidentUnitToUserCommandHandler;
use App\Context\User\Infrastructure\Http\Dto\LinkResidentUnitToUserRequestDto;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Put(
    path: '/api/v1/users/{id}/resident-unit',
    summary: 'Link resident unit to user',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['residentUnitId'],
            properties: [
                new OA\Property(property: 'residentUnitId', type: 'string', format: 'uuid'),
            ],
        ),
    ),
    tags: ['Users'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 204, description: 'Resident unit linked.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final readonly class LinkResidentUnitToUserPutController
{
    public function __construct(private LinkResidentUnitToUserCommandHandler $handler) {}

    public function __invoke(string $id, LinkResidentUnitToUserRequestDto $request): Response
    {
        if ($request->residentUnitId === null || $request->residentUnitId === '') {
            throw new InvalidArgumentException('residentUnitId is required');
        }

        $command = new LinkResidentUnitToUserCommand(
            $id,
            $request->residentUnitId,
        );

        $this->handler->__invoke($command);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
