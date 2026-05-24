<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Application\UseCase\AppendRecipients\AppendRecipientsCommand;
use App\Context\ResidentUnit\Application\UseCase\AppendRecipients\AppendRecipientsCommandHandler;
use App\Context\ResidentUnit\Domain\Exception\ResidentUnitNotFoundException;
use App\Context\ResidentUnit\Infrastructure\Http\Dto\AppendRecipientsRequestDto;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Patch(
    path: '/api/v1/resident-unit/{id}/recipients',
    summary: 'Append notification recipient to resident unit',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'email'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
            ],
        ),
    ),
    tags: ['Resident Units'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Recipient appended.'),
        new OA\Response(response: 404, description: 'Resident unit not found.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final readonly class ResidentUnitAppendRecipientsController
{
    public function __construct(private AppendRecipientsCommandHandler $commandHandler) {}

    /**
     * @throws ResidentUnitNotFoundException
     */
    public function __invoke(AppendRecipientsRequestDto $requestDto): Response
    {
        $command = new AppendRecipientsCommand(
            $requestDto->id,
            $requestDto->name,
            $requestDto->email,
        );

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_OK);
    }
}
