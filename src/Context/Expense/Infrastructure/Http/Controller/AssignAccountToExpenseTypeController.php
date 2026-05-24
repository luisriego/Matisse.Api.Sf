<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\AssignAccountTypeToExpenseType\AssignAccountTypeToExpenseTypeCommand;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[OA\Patch(
    path: '/api/v1/expense-types/{expenseTypeId}/assign-account/{accountTypeId}',
    summary: 'Assign account type to expense type',
    parameters: [
        new OA\Parameter(name: 'expenseTypeId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'accountTypeId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    tags: ['Expenses'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Account type assigned to expense type'),
        new OA\Response(response: 400, description: 'Validation error'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class AssignAccountToExpenseTypeController extends ApiController
{
    /**
     * @throws Throwable
     */
    public function __invoke(string $expenseTypeId, string $accountTypeId): JsonResponse
    {
        $this->dispatch(new AssignAccountTypeToExpenseTypeCommand(
            $expenseTypeId,
            $accountTypeId,
        ));

        return new JsonResponse(null, Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
