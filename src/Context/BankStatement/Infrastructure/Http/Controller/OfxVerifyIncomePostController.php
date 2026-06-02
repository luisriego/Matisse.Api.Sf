<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Http\Controller;

use App\Context\BankStatement\Application\Dto\CreditLineDto;
use App\Context\BankStatement\Application\Dto\VerifyIncomeResultDto;
use App\Context\BankStatement\Application\UseCase\VerifyIncome\VerifyIncomeQuery;
use App\Context\BankStatement\Infrastructure\Http\Dto\VerifyIncomeRequestDto;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use function array_map;

#[OA\Post(
    path: '/api/v1/bank/ofx-verify-income',
    operationId: 'bankOfxVerifyIncome',
    summary: 'Verify OFX credit lines against expected slip income for a given month (read-only).',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['month', 'year', 'creditLines'],
            properties: [
                new OA\Property(property: 'month', type: 'integer', example: 3, description: 'Month 1–12'),
                new OA\Property(property: 'year', type: 'integer', example: 2026),
                new OA\Property(
                    property: 'creditLines',
                    type: 'array',
                    items: new OA\Items(
                        required: ['importLineKey', 'amountInCents', 'memo'],
                        properties: [
                            new OA\Property(
                                property: 'importLineKey',
                                type: 'string',
                                example: 'CR-20260310-001',
                                description: 'Echo from preview. Older payloads may use the previous JSON property name for this field.',
                            ),
                            new OA\Property(property: 'amountInCents', type: 'integer', example: 25000),
                            new OA\Property(property: 'memo', type: 'string', example: 'BOLETOS RECEBIDOS 10/03S'),
                        ],
                    ),
                ),
            ],
        ),
    ),
    tags: ['Bank / OFX'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Income reconciliation result.',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'expectedInCents',
                        type: 'integer',
                        example: 50000,
                        description: 'Total of non-cancelled slips due in the given month.',
                    ),
                    new OA\Property(
                        property: 'receivedInCents',
                        type: 'integer',
                        example: 50000,
                        description: 'Sum of all credit lines passed in the request.',
                    ),
                    new OA\Property(
                        property: 'differenceInCents',
                        type: 'integer',
                        example: 0,
                        description: 'received − expected. Negative = shortfall, positive = surplus.',
                    ),
                    new OA\Property(
                        property: 'status',
                        type: 'string',
                        enum: ['balanced', 'shortfall', 'surplus'],
                        example: 'balanced',
                    ),
                    new OA\Property(property: 'totalSlips', type: 'integer', example: 2),
                    new OA\Property(property: 'paidSlips', type: 'integer', example: 2),
                    new OA\Property(
                        property: 'unpaidSlips',
                        type: 'array',
                        description: 'Empty when status is balanced or surplus.',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'slipId', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'amountInCents', type: 'integer', example: 25000),
                                new OA\Property(property: 'status', type: 'string', example: 'issued'),
                                new OA\Property(property: 'residentUnitId', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'dueDate', type: 'string', format: 'date', example: '2026-03-10'),
                            ],
                        ),
                    ),
                ],
            ),
        ),
    ],
)]
final class OfxVerifyIncomePostController extends ApiController
{
    public function __invoke(VerifyIncomeRequestDto $request): JsonResponse
    {
        $creditLines = array_map(
            static fn (array $line) => new CreditLineDto(
                importLineKey: $line['importLineKey'],
                amountInCents: $line['amountInCents'],
                memo: $line['memo'],
            ),
            $request->creditLines,
        );

        /** @var VerifyIncomeResultDto $result */
        $result = $this->ask(new VerifyIncomeQuery(
            month: $request->month,
            year: $request->year,
            creditLines: $creditLines,
        ));

        return new JsonResponse([
            'expectedInCents'   => $result->expectedInCents,
            'receivedInCents'   => $result->receivedInCents,
            'differenceInCents' => $result->differenceInCents,
            'status'            => $result->status,
            'totalSlips'        => $result->totalSlips,
            'paidSlips'         => $result->paidSlips,
            'unpaidSlips'       => array_map(
                static fn ($slip) => [
                    'slipId'         => $slip->slipId,
                    'amountInCents'  => $slip->amountInCents,
                    'status'         => $slip->status,
                    'residentUnitId' => $slip->residentUnitId,
                    'dueDate'        => $slip->dueDate,
                ],
                $result->unpaidSlips,
            ),
        ], Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [];
    }
}
