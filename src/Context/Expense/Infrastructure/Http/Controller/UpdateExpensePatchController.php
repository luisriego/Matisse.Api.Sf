<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\UpdateExpense\UpdateExpenseCommand;
use App\Context\Expense\Application\UseCase\UpdateExpense\UpdateExpenseCommandHandler;
use App\Context\Expense\Infrastructure\Http\Dto\UpdateExpenseRequestDto;
use DateMalformedStringException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Patch(
    path: '/api/v1/expenses/update/{id}',
    summary: 'Update expense due date and description',
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
    tags: ['Expenses'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 204, description: 'Expense updated'),
        new OA\Response(response: 400, description: 'Validation error'),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 404, description: 'Expense not found'),
    ],
)]
final readonly class UpdateExpensePatchController
{
    public function __construct(private UpdateExpenseCommandHandler $commandHandler) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(string $id, UpdateExpenseRequestDto $request): Response
    {
        $command = new UpdateExpenseCommand(
            $id,
            $request->dueDate,
            $request->description,
        );

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
