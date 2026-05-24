<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\EnterExpense\EnterMonthlyRecurringExpenseCommand;
use App\Context\Expense\Infrastructure\Http\Dto\EnterMonthlyRecurringExpenseRequestDto;
use App\Shared\Domain\Exception\InvalidDataException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Exception;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[OA\Put(
    path: '/api/v1/recurring-expenses/enter-monthly',
    summary: 'Enter monthly expense from recurring template',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['id', 'recurringExpenseId', 'accountId', 'amount', 'date'],
            properties: [
                new OA\Property(property: 'id', type: 'string', format: 'uuid', description: 'New expense ID'),
                new OA\Property(property: 'recurringExpenseId', type: 'string', format: 'uuid'),
                new OA\Property(property: 'accountId', type: 'string', format: 'uuid'),
                new OA\Property(property: 'amount', type: 'integer', description: 'Amount in cents'),
                new OA\Property(property: 'date', type: 'string', format: 'date', description: 'Due date for this occurrence'),
            ],
        ),
    ),
    tags: ['Expenses'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 201, description: 'Monthly recurring expense entered'),
        new OA\Response(response: 400, description: 'Validation error'),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 404, description: 'Recurring expense not found'),
    ],
)]
final class EnterMonthlyRecurringExpenseController extends ApiController
{
    /**
     * @throws Exception|Throwable
     */
    public function __invoke(EnterMonthlyRecurringExpenseRequestDto $dto): Response
    {
        $command = new EnterMonthlyRecurringExpenseCommand(
            $dto->id->value(), // ID do novo gasto, do DTO
            $dto->recurringExpenseId->value(), // ID da plantilla, do DTO
            $dto->accountId->value(),
            $dto->amount->value(),
            $dto->date->value(),
        );

        $this->dispatch($command);

        return new Response(null, Response::HTTP_CREATED);
    }

    public function exceptions(): array
    {
        return [
            ResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
            InvalidDataException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
