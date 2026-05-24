<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\CompensateExpense\CompensateExpenseCommand;
use App\Context\Expense\Application\UseCase\CompensateExpense\CompensateExpenseCommandHandler;
use App\Context\Expense\Infrastructure\Http\Dto\CompensateExpenseRequestDto;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Patch(
    path: '/api/v1/expenses/compensate/{id}',
    summary: 'Compensate expense amount',
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'amount', type: 'integer', nullable: true, description: 'Compensation amount in cents'),
            ],
        ),
    ),
    tags: ['Expenses'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 204, description: 'Expense compensated'),
        new OA\Response(response: 400, description: 'Validation error'),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 404, description: 'Expense not found'),
    ],
)]
final readonly class CompensateExpensePatchController
{
    public function __construct(private CompensateExpenseCommandHandler $commandHandler) {}

    public function __invoke(string $id, CompensateExpenseRequestDto $request): Response
    {
        $command = new CompensateExpenseCommand(
            $id,
            $request->amount,
        );

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
