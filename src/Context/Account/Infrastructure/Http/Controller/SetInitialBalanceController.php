<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Application\UseCase\SetInitialBalance\SetInitialBalanceCommand;
use App\Shared\Domain\Exception\InvalidDataException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;

final class SetInitialBalanceController extends ApiController
{
    public function __invoke(string $id, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['amount']) || !isset($data['date'])) {
            throw new InvalidDataException('The fields "amount" and "date" are required.');
        }

        $command = new SetInitialBalanceCommand(
            $id,
            (int) $data['amount'],
            (string) $data['date'],
        );

        $this->dispatch($command);

        return new Response(null, Response::HTTP_ACCEPTED);
    }

    protected function exceptions(): array
    {
        return [
            ResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
            InvalidDataException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
