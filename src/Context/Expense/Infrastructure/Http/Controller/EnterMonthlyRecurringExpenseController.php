<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\EnterExpense\EnterMonthlyRecurringExpenseCommand;
use App\Context\Expense\Infrastructure\Http\Dto\EnterMonthlyRecurringExpenseRequestDto;
use App\Shared\Domain\Exception\InvalidDataException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Exception;
use Symfony\Component\HttpFoundation\Response;

final class EnterMonthlyRecurringExpenseController extends ApiController
{
    /**
     * @throws Exception
     */
    public function __invoke(EnterMonthlyRecurringExpenseRequestDto $dto): Response
    {
        $command = new EnterMonthlyRecurringExpenseCommand(
            $dto->id->value(), // ID do novo gasto, do DTO
            $dto->recurringExpenseId->value(), // ID da plantilla, do DTO
            $dto->accountId->value(),
            $dto->amount->value(),
            $dto->date->value(),
        );

        $this->dispatch($command);

        return new Response(null, Response::HTTP_CREATED);
    }

    protected function exceptions(): array
    {
        return [
            ResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
            InvalidDataException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
