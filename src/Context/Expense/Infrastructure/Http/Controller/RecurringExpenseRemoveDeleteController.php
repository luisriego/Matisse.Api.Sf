<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\RemoveRecurringExpense\RemoveRecurringExpenseCommand;
use App\Context\Expense\Application\UseCase\RemoveRecurringExpense\RemoveRecurringExpenseCommandHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class RecurringExpenseRemoveDeleteController
{
    public function __construct(private RemoveRecurringExpenseCommandHandler $commandHandler) {}

    public function __invoke(string $id): JsonResponse
    {
        $command = new RemoveRecurringExpenseCommand($id);

        $this->commandHandler->__invoke($command);

        return new JsonResponse('', Response::HTTP_NO_CONTENT);
    }
}
