<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\PayedAtExpense\ExpensePayedCommandHandler;
use App\Context\Expense\Application\UseCase\PayedAtExpense\PayedAtExpenseCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Patch(
    path: '/api/v1/expenses/payed/{id}',
    summary: 'Mark expense as paid',
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    tags: ['Expenses'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 204, description: 'Expense marked as paid'),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 404, description: 'Expense not found'),
    ],
)]
final readonly class ExpensePayedPatchController
{
    public function __construct(private ExpensePayedCommandHandler $commandHandler) {}

    public function __invoke(string $id): Response
    {
        $command = new PayedAtExpenseCommand($id);

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
