<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\UpdateExpense\UpdateExpenseCommand;
use App\Context\Expense\Application\UseCase\UpdateExpense\UpdateExpenseCommandHandler;
use App\Context\Expense\Infrastructure\Http\Dto\UpdateExpenseRequestDto;
use DateMalformedStringException;
use Symfony\Component\HttpFoundation\Response;

final readonly class UpdateExpensePatchController
{
    public function __construct(private UpdateExpenseCommandHandler $commandHandler) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(string $id, UpdateExpenseRequestDto $request): Response
    {
        $command = new UpdateExpenseCommand(
            $id,
            $request->dueDate,
            $request->description,
        );

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
