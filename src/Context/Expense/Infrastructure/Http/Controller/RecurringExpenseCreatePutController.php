<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\EnterExpense\CreateRecurringExpenseCommand;
use App\Context\Expense\Application\UseCase\EnterExpense\CreateRecurringExpenseCommandHandler;
use App\Context\Expense\Infrastructure\Http\Dto\CreateRecurringExpenseRequestDto;
use DateMalformedStringException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Put(
    path: '/api/v1/recurring-expenses/create',
    summary: 'Create a recurring expense template',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['id', 'amount', 'type', 'accountId', 'dueDay', 'monthsOfYear'],
            properties: [
                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'amount', type: 'integer', description: 'Amount in cents'),
                new OA\Property(property: 'type', type: 'string', format: 'uuid', description: 'Expense type ID'),
                new OA\Property(property: 'accountId', type: 'string', format: 'uuid'),
                new OA\Property(property: 'dueDay', type: 'integer', description: 'Day of month (1–31)'),
                new OA\Property(property: 'monthsOfYear', type: 'array', items: new OA\Items(type: 'integer'), description: 'Months when the expense applies (1–12)'),
                new OA\Property(property: 'startDate', type: 'string', format: 'date'),
                new OA\Property(property: 'endDate', type: 'string', format: 'date'),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'notes', type: 'string'),
                new OA\Property(property: 'hasPredefinedAmount', type: 'boolean', description: 'Whether amount is fixed for each occurrence'),
            ],
        ),
    ),
    tags: ['Expenses'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 201, description: 'Recurring expense created'),
        new OA\Response(response: 400, description: 'Validation error'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
readonly class RecurringExpenseCreatePutController
{
    public function __construct(private CreateRecurringExpenseCommandHandler $commandHandler) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(CreateRecurringExpenseRequestDto $request): Response
    {
        $command = new CreateRecurringExpenseCommand(
            $request->id,
            $request->amount,
            $request->type,
            $request->accountId,
            $request->dueDay,
            $request->monthsOfYear,
            $request->startDate,
            $request->endDate,
            $request->description,
            $request->notes,
            $request->hasPredefinedAmount, // Pass the new field to the command
        );

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_CREATED);
    }
}
