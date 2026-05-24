<?php

declare(strict_types=1);

namespace App\Context\Income\Infrastructure\Http\Controller;

use App\Context\Income\Application\UseCase\UpdateIncome\UpdateIncomeCommand;
use App\Context\Income\Infrastructure\Http\Dto\UpdateIncomeRequestDto;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Patch(
    path: '/api/v1/incomes/update/{id}',
    summary: 'Update income due date and description',
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'dueDate', type: 'string', format: 'date', nullable: true),
                new OA\Property(property: 'description', type: 'string', nullable: true),
            ],
        ),
    ),
    tags: ['Incomes'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 204, description: 'Income updated'),
        new OA\Response(response: 400, description: 'Validation error'),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 404, description: 'Income not found'),
    ],
)]
final class UpdateIncomePatchController extends ApiController
{
    public function __invoke(string $id, UpdateIncomeRequestDto $request): Response
    {
        $command = new UpdateIncomeCommand(
            $id,
            $request->dueDate,
            $request->description,
        );

        $this->dispatch($command);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    public function exceptions(): array
    {
        return [
            ResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
