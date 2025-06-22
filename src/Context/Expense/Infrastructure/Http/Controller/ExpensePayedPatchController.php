<?php

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Account\Application\UseCase\EnableAccount\EnableAccountCommandHandler;
use App\Context\Expense\Application\UseCase\PayedAtExpense\ExpensePayedCommandHandler;
use App\Context\Expense\Application\UseCase\PayedAtExpense\PayedAtExpenseCommand;
use Symfony\Component\HttpFoundation\Response;

final readonly class ExpensePayedPatchController
{
    public function __construct(private ExpensePayedCommandHandler $commandHandler)
    {
    }

    public function __invoke(string $id): Response
    {
        $command = new PayedAtExpenseCommand($id);

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}