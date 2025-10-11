<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\EnterExpense\EnterExpenseCommand;
use App\Context\Expense\Application\UseCase\EnterExpense\EnterExpenseCommandHandler;
use App\Context\Expense\Infrastructure\Http\Dto\EnterExpenseRequestDto;
use DateMalformedStringException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class ExpenseEnterPutController
{
    public function __construct(private EnterExpenseCommandHandler $commandHandler) {}

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

        return new JsonResponse($expense->toArray(), Response::HTTP_CREATED);
    }
}
