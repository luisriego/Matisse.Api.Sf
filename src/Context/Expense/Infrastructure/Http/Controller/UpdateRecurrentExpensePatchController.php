<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\UpdateRecurringExpense\UpdateRecurrentExpenseCommand;
use App\Context\Expense\Application\UseCase\UpdateRecurringExpense\UpdateRecurrentExpenseCommandHandler;
use App\Context\Expense\Infrastructure\Http\Dto\UpdateRecurrentExpenseRequestDto;
use DateMalformedStringException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Patch(
    path: '/api/v1/recurring-expenses/{id}',
    summary: 'Update a recurring expense template',
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'amount', type: 'integer', nullable: true, description: 'Amount in cents'),
                new OA\Property(property: 'type', type: 'string', format: 'uuid', nullable: true, description: 'Expense type ID'),
                new OA\Property(property: 'dueDay', type: 'integer', nullable: true, description: 'Day of month (1–31)'),
                new OA\Property(property: 'monthsOfYear', type: 'array', items: new OA\Items(type: 'integer'), nullable: true),
                new OA\Property(property: 'startDate', type: 'string', format: 'date', nullable: true),
                new OA\Property(property: 'endDate', type: 'string', format: 'date', nullable: true),
                new OA\Property(property: 'description', type: 'string', nullable: true),
                new OA\Property(property: 'notes', type: 'string', nullable: true),
            ],
        ),
    ),
    tags: ['Expenses'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 204, description: 'Recurring expense updated'),
        new OA\Response(response: 400, description: 'Validation error'),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 404, description: 'Recurring expense not found'),
    ],
)]
final readonly class UpdateRecurrentExpensePatchController
{
    public function __construct(private readonly UpdateRecurrentExpenseCommandHandler $commandHandler) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(string $id, UpdateRecurrentExpenseRequestDto $request): Response
    {
        $command = new UpdateRecurrentExpenseCommand(
            $id,
            $request->amount,
            $request->type,
            $request->dueDay,
            $request->monthsOfYear,
            $request->startDate,
            $request->endDate,
            $request->description,
            $request->notes,
        );

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
