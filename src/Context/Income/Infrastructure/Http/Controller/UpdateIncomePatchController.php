<?php

declare(strict_types=1);

namespace App\Context\Income\Infrastructure\Http\Controller;

use App\Context\Income\Application\UseCase\UpdateIncome\UpdateIncomeCommand;
use App\Context\Income\Application\UseCase\UpdateIncome\UpdateIncomeCommandHandler;
use App\Context\Income\Infrastructure\Http\Dto\UpdateIncomeRequestDto;
use DateMalformedStringException;
use Symfony\Component\HttpFoundation\Response;

final readonly class UpdateIncomePatchController
{
    public function __construct(private UpdateIncomeCommandHandler $commandHandler) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(string $id, UpdateIncomeRequestDto $request): Response
    {
        $command = new UpdateIncomeCommand(
            $id,
            $request->dueDate,
            $request->description,
        );

        $this->commandHandler->__invoke($command);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
