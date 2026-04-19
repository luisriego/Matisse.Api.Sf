<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Http\Controller;

use App\Context\BankStatement\Application\UseCase\PreviewBankStatement\PreviewBankStatementQuery;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * First step of OFX ingestion: parse file, match history, return lines for review (no persistence).
 */
#[OA\Post(
    path: '/api/v1/bank/ofx-ingest',
    operationId: 'bankOfxIngest',
    summary: 'Parse an OFX file and return a preview for user review (no data is persisted).',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['file'],
                properties: [
                    new OA\Property(property: 'file', type: 'string', format: 'binary',
                        description: 'OFX bank statement file (.ofx)'),
                ],
            ),
        ),
    ),
    tags: ['Bank / OFX'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Preview generated. No data has been persisted.',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'bankId',               type: 'string', example: '0260'),
                    new OA\Property(property: 'accountId',            type: 'string', example: '12345-6'),
                    new OA\Property(property: 'currency',             type: 'string', example: 'BRL'),
                    new OA\Property(property: 'periodStart',          type: 'string', format: 'date', example: '2026-03-01'),
                    new OA\Property(property: 'periodEnd',            type: 'string', format: 'date', example: '2026-03-31'),
                    new OA\Property(property: 'ledgerBalanceInCents', type: 'integer', nullable: true, example: 250000),
                    new OA\Property(property: 'ledgerBalanceDate',    type: 'string',  format: 'date', nullable: true),
                    new OA\Property(property: 'totalNeedsReview',     type: 'integer', example: 5),
                    new OA\Property(property: 'totalPreFilled',       type: 'integer', example: 12),
                    new OA\Property(
                        property: 'expenses',
                        description: 'DEBIT lines to classify as condominium expenses.',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'fitId',                       type: 'string', example: 'FIT-20260310-001'),
                                new OA\Property(property: 'bankAccountId',               type: 'string', format: 'uuid'),
                                new OA\Property(property: 'type',                        type: 'string', enum: ['DEBIT', 'CREDIT']),
                                new OA\Property(property: 'amountInCents',               type: 'integer', example: 15000),
                                new OA\Property(property: 'postedAt',                    type: 'string', format: 'date'),
                                new OA\Property(property: 'memo',                        type: 'string', example: 'COPASA AGUA'),
                                new OA\Property(property: 'status',                      type: 'string', enum: ['needs_review', 'pre_filled']),
                                new OA\Property(property: 'isNew',                       type: 'boolean'),
                                new OA\Property(property: 'confidence',                  type: 'number', format: 'float', example: 0.9),
                                new OA\Property(property: 'suggestedExpenseTypeId',      type: 'string', format: 'uuid', nullable: true),
                                new OA\Property(property: 'suggestedExpenseTypeName',    type: 'string', nullable: true),
                                new OA\Property(property: 'suggestedRecurringExpenseId', type: 'string', format: 'uuid', nullable: true),
                                new OA\Property(property: 'suggestedAccountId',          type: 'string', format: 'uuid', nullable: true),
                                new OA\Property(property: 'suggestedResidentUnitId',     type: 'string', format: 'uuid', nullable: true),
                                new OA\Property(
                                    property: 'pastAssignments',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'month',                type: 'integer', example: 3),
                                            new OA\Property(property: 'year',                 type: 'integer', example: 2026),
                                            new OA\Property(property: 'amountInCents',        type: 'integer', example: 15000),
                                            new OA\Property(property: 'expenseTypeId',        type: 'string', format: 'uuid', nullable: true),
                                            new OA\Property(property: 'expenseTypeName',      type: 'string', nullable: true),
                                            new OA\Property(property: 'recurringExpenseId',   type: 'string', format: 'uuid', nullable: true),
                                            new OA\Property(property: 'recurringExpenseName', type: 'string', nullable: true),
                                            new OA\Property(property: 'accountId',            type: 'string', format: 'uuid', nullable: true),
                                            new OA\Property(property: 'residentUnitId',       type: 'string', format: 'uuid', nullable: true),
                                            new OA\Property(property: 'confidence',           type: 'number', format: 'float', example: 0.9),
                                        ],
                                    ),
                                ),
                                new OA\Property(
                                    property: 'embeddingCandidates',
                                    description: 'Top-K semantic candidates (empty = Ollama unavailable).',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'candidateId',    type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'label',          type: 'string', example: 'COPASA água fatura'),
                                            new OA\Property(property: 'score',          type: 'number', format: 'float', example: 0.9842),
                                            new OA\Property(property: 'embeddingModel', type: 'string', example: 'nomic-embed-text'),
                                        ],
                                    ),
                                ),
                            ],
                        ),
                    ),
                    new OA\Property(
                        property: 'credits',
                        description: 'CREDIT lines for income reconciliation.',
                        type: 'array',
                        items: new OA\Items(type: 'object'),
                    ),
                ],
            ),
        ),
        new OA\Response(
            response: 400,
            description: 'Empty OFX file.',
            content: new OA\JsonContent(
                properties: [new OA\Property(property: 'error', type: 'string')],
            ),
        ),
        new OA\Response(
            response: 422,
            description: 'Missing file field or unparseable OFX content.',
            content: new OA\JsonContent(
                properties: [new OA\Property(property: 'error', type: 'string')],
            ),
        ),
    ],
)]
final class OfxIngestPostController extends ApiController
{
    public function __construct(
        MessageBusInterface $commandBus,
        MessageBusInterface $queryBus,
        private readonly NormalizerInterface $normalizer,
    ) {
        parent::__construct($commandBus, $queryBus);
    }

    public function __invoke(Request $request): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile) {
            return new JsonResponse(
                ['error' => 'Se requiere un archivo OFX (multipart/form-data, campo "file").'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $ofxContent = (string) file_get_contents($file->getPathname());

        if ($ofxContent === '') {
            return new JsonResponse(['error' => 'El archivo OFX está vacío.'], Response::HTTP_BAD_REQUEST);
        }

        $preview = $this->ask(new PreviewBankStatementQuery($ofxContent));

        return new JsonResponse(
            $this->normalizer->normalize($preview),
            Response::HTTP_OK,
        );
    }

    public function exceptions(): array
    {
        return [
            RuntimeException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
