<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\CompensateExpense\CompensateExpenseCommand;
use App\Context\Expense\Application\UseCase\CompensateExpense\CompensateExpenseCommandHandler;
use App\Context\Expense\Infrastructure\Http\Dto\CompensateExpenseRequestDto;
use Symfony\Component\HttpFoundation\Response;

final readonly class CompensateExpensePatchController
{
    public function __construct(private CompensateExpenseCommandHandler $commandHandler) {}

    /**
     */
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
