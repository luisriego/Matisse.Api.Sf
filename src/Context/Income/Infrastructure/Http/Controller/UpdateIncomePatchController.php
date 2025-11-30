<?php

declare(strict_types=1);

namespace App\Context\Income\Infrastructure\Http\Controller;

use App\Context\Income\Application\UseCase\UpdateIncome\UpdateIncomeCommand;
use App\Context\Income\Infrastructure\Http\Dto\UpdateIncomeRequestDto;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\Response;

final class UpdateIncomePatchController extends ApiController
{
    public function __invoke(string $id, UpdateIncomeRequestDto $request): Response
    {
        $command = new UpdateIncomeCommand(
            $id,
            $request->dueDate,
            $request->description,
        );

        $this->dispatch($command);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    public function exceptions(): array
    {
        return [
            ResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
