<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Application\UseCase\SetInitialBalance\SetInitialBalanceCommand;
use App\Shared\Domain\Exception\InvalidDataException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function json_decode;

#[OA\Put(
    path: '/api/v1/accounts/{id}/initial-balance',
    summary: 'Set account opening balance',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['amount', 'date'],
            properties: [
                new OA\Property(property: 'amount', type: 'integer', description: 'Opening balance in cents'),
                new OA\Property(property: 'date', type: 'string', format: 'date', description: 'Effective date (Y-m-d)'),
            ],
        ),
    ),
    tags: ['Accounts'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 202, description: 'Initial balance accepted.'),
        new OA\Response(response: 400, description: 'Validation error.'),
        new OA\Response(response: 404, description: 'Account not found.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final class SetInitialBalanceController extends ApiController
{
    /**
     * @throws Throwable
     */
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

    public function exceptions(): array
    {
        return [
            ResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
            InvalidDataException::class => Response::HTTP_BAD_REQUEST,
        ];
    }
}
