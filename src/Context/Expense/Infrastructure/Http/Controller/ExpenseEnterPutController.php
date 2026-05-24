<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\EnterExpense\EnterExpenseCommand;
use App\Context\Expense\Application\UseCase\EnterExpense\EnterExpenseCommandHandler;
use App\Context\Expense\Infrastructure\Http\Dto\EnterExpenseRequestDto;
use DateMalformedStringException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Put(
    path: '/api/v1/expenses/enter',
    summary: 'Enter a new expense',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['id', 'amount', 'type', 'accountId', 'dueDate'],
            properties: [
                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'amount', type: 'integer', description: 'Amount in cents'),
                new OA\Property(property: 'type', type: 'string', format: 'uuid', description: 'Expense type ID'),
                new OA\Property(property: 'accountId', type: 'string', format: 'uuid'),
                new OA\Property(property: 'dueDate', type: 'string', format: 'date'),
                new OA\Property(property: 'isActive', type: 'boolean', nullable: true),
                new OA\Property(property: 'description', type: 'string', nullable: true),
                new OA\Property(property: 'residentUnitId', type: 'string', format: 'uuid', nullable: true),
            ],
        ),
    ),
    tags: ['Expenses'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 201, description: 'Expense created; returns the normalized expense resource.'),
        new OA\Response(response: 400, description: 'Validation error'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final readonly class ExpenseEnterPutController
{
    public function __construct(
        private EnterExpenseCommandHandler $commandHandler,
        private readonly NormalizerInterface $normalizer,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(EnterExpenseRequestDto $request): Response
    {
        $command = new EnterExpenseCommand(
            $request->id,
            $request->amount,
            $request->type,
            $request->accountId,
            $request->dueDate,
            $request->isActive,
            $request->description,
            $request->residentUnitId,
        );

        $expense = $this->commandHandler->__invoke($command);

        $data = $this->normalizer->normalize($expense);

        return new JsonResponse($data, Response::HTTP_CREATED);
    }
}
