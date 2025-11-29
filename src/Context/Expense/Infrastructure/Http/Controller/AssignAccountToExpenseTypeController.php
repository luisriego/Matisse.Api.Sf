<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\AssignAccountTypeToExpenseType\AssignAccountTypeToExpenseTypeCommand;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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
