<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\EnterExpense\EnterExpenseCommand;
use App\Context\Expense\Infrastructure\Http\Dto\EnterExpenseRequestDto;
use Symfony\Component\HttpFoundation\Response;

final readonly class ExpenseEnterPutController
{
    public function __construct(private EnterExpenseCommandHandler $commandHandler) {}

    public function __invoke(EnterExpenseRequestDto $request): Response
    {
        $command = new EnterExpenseCommand(
            $request->id,
            $request->amount,
            $request->accountId,
            $request->dueDate,
        );

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_CREATED);
    }
}
