<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\UpdateRecurringExpense\UpdateRecurrentExpenseCommand;
use App\Context\Expense\Application\UseCase\UpdateRecurringExpense\UpdateRecurrentExpenseCommandHandler;
use App\Context\Expense\Infrastructure\Http\Dto\UpdateRecurrentExpenseRequestDto;
use DateMalformedStringException;
use Symfony\Component\HttpFoundation\Response;

final readonly class UpdateRecurrentExpensePatchController
{
    public function __construct(private readonly UpdateRecurrentExpenseCommandHandler $commandHandler) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(string $id, UpdateRecurrentExpenseRequestDto $request): Response
    {
        $command = new UpdateRecurrentExpenseCommand(
            $id,
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

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
