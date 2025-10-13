<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\EnterExpense\EnterExpenseWithDescriptionCommand;
use App\Context\Expense\Application\UseCase\EnterExpense\EnterExpenseWithDescriptionCommandHandler;
use App\Context\Expense\Infrastructure\Http\Dto\EnterExpenseWithDescriptionRequestDto;
use DateMalformedStringException;
use Symfony\Component\HttpFoundation\Response;

final readonly class ExpenseEnterWithDescriptionPutController
{
    public function __construct(private EnterExpenseWithDescriptionCommandHandler $commandHandler) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(EnterExpenseWithDescriptionRequestDto $request): Response
    {
        $command = new EnterExpenseWithDescriptionCommand(
            $request->id,
            $request->amount,
            $request->type,
            $request->accountId,
            $request->dueDate,
            $request->description,
        );

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_CREATED);
    }
}
