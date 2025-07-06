<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\EnterExpense\CreateRecurringExpenseCommand;
use App\Context\Expense\Application\UseCase\EnterExpense\CreateRecurringExpenseCommandHandler;
use App\Context\Expense\Infrastructure\Http\Dto\CreateRecurringExpenseRequestDto;
use DateMalformedStringException;
use Symfony\Component\HttpFoundation\Response;

readonly class RecurringExpenseCreatePutController
{
    public function __construct(private CreateRecurringExpenseCommandHandler $commandHandler) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(CreateRecurringExpenseRequestDto $request): Response
    {
        $command = new CreateRecurringExpenseCommand(
            $request->id,
            $request->amount,
            $request->type,
            $request->dueDay,
            $request->monthsOfYear,
            $request->startDate,
            $request->endDate,
            $request->description,
            $request->notes,
        );

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_CREATED);
    }
}
