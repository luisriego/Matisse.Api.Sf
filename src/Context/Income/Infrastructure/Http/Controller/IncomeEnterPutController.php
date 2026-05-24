<?php

declare(strict_types=1);

namespace App\Context\Income\Infrastructure\Http\Controller;

use App\Context\Income\Application\UseCase\EnterIncome\EnterIncomeCommandHandler;
use App\Context\Income\Infrastructure\Http\Dto\EnterIncomeRequestDto;
use DateMalformedStringException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Put(
    path: '/api/v1/incomes/enter',
    summary: 'Enter a new income',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['id', 'amount', 'type', 'accountId', 'dueDate'],
            properties: [
                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'amount', type: 'integer', description: 'Amount in cents'),
                new OA\Property(property: 'type', type: 'string', format: 'uuid', description: 'Income type ID'),
                new OA\Property(property: 'accountId', type: 'string', format: 'uuid'),
                new OA\Property(property: 'dueDate', type: 'string', format: 'date'),
                new OA\Property(property: 'residentUnitId', type: 'string', format: 'uuid', nullable: true),
                new OA\Property(property: 'description', type: 'string', nullable: true),
            ],
        ),
    ),
    tags: ['Incomes'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 201, description: 'Income created'),
        new OA\Response(response: 400, description: 'Validation error'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
final readonly class IncomeEnterPutController
{
    public function __construct(private EnterIncomeCommandHandler $commandHandler) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(EnterIncomeRequestDto $request): Response
    {
        $this->commandHandler->__invoke($request->toCommand());

        return new Response('', Response::HTTP_CREATED);
    }
}
