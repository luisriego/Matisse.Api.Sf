<?php

declare(strict_types=1);

namespace App\Context\Income\Infrastructure\Http\Controller;

use App\Context\Income\Application\UseCase\EnterIncome\EnterIncomeCommand;
use App\Context\Income\Application\UseCase\EnterIncome\EnterIncomeCommandHandler;
use App\Context\Income\Infrastructure\Http\Dto\EnterIncomeRequestDto;
use DateMalformedStringException;
use Symfony\Component\HttpFoundation\Response;

final readonly class IncomeEnterPutController
{
    public function __construct(private EnterIncomeCommandHandler $commandHandler) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(EnterIncomeRequestDto $request): Response
    {
        $command = new EnterIncomeCommand(
            $request->id,
            $request->amount,
            $request->residentUnitId,
            $request->type,
            $request->dueDate,
            $request->isActive,
            $request->description,
        );

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_CREATED);
    }
}
