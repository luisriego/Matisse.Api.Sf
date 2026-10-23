<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Http\Controller;

use App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines\ConfirmBankOfxLinesCommand;
use App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines\ConfirmBankOfxLinesCommandHandler;
use App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines\ConfirmLineDto;
use App\Context\BankStatement\Domain\Exception\BoletoSettlementMismatchException;
use App\Context\BankStatement\Infrastructure\Http\Dto\ConfirmBankOfxLinesRequestDto;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Income\Domain\IncomeRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use function array_filter;
use function array_map;
use function array_values;
use function dechex;
use function hash;
use function hexdec;
use function mb_substr;
use function sprintf;

#[OA\Post(
    path: '/api/v1/bank/ofx-confirm',
    operationId: 'bankOfxConfirm',
    summary: 'Confirm reviewed OFX lines — persists expenses / incomes.',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['bankAccountId', 'lines'],
            properties: [
                new OA\Property(
                    property: 'bankAccountId',
                    type: 'string',
                    description: 'Bank reference from the OFX file (e.g. ACCTID). Optional for booking if the condominium already has active ledger accounts or DEFAULT_BANK_LEDGER_ACCOUNT_ID / setup openingReference.ledgerAccountId.',
                    example: '3033132774',
                ),
                new OA\Property(
                    property: 'lines',
                    type: 'array',
                    items: new OA\Items(
                        required: ['importLineKey', 'amountInCents', 'postedAt', 'memo', 'dueDate'],
                        properties: [
                            new OA\Property(
                                property: 'importLineKey',
                                type: 'string',
                                example: '20260310001',
                                description: 'Echo importLineKey from /bank/ofx-ingest. Older payloads may use the previous JSON property name for this field.',
                            ),
                            new OA\Property(property: 'amountInCents', type: 'integer', example: 15000),
                            new OA\Property(property: 'postedAt', type: 'string', format: 'date', example: '2026-03-10'),
                            new OA\Property(property: 'memo', type: 'string', example: 'COPASA AGUA'),
                            new OA\Property(
                                property: 'lineType',
                                type: 'string',
                                enum: ['expense', 'income'],
                                example: 'expense',
                                description: 'Defaults to "expense" when omitted (DEBIT legacy).',
                            ),
                            new OA\Property(
                                property: 'creditKind',
                                type: 'string',
                                enum: ['boleto_settlement', 'other'],
                                example: 'boleto_settlement',
                                description: 'Only applies to income lines. "boleto_settlement" lines consolidate into one monthly income. If the previous-month Slip total in DB is positive, it must match the bank sum; if it is zero (no slips), the bank sum is accepted as the initial total. "other" credits become individual incomes.',
                            ),
                            new OA\Property(
                                property: 'expenseTypeId',
                                type: 'string',
                                format: 'uuid',
                                nullable: true,
                                description: 'Required for expense lines.',
                            ),
                            new OA\Property(
                                property: 'incomeTypeId',
                                type: 'string',
                                format: 'uuid',
                                nullable: true,
                                description: 'Settlement lines fall back to DEFAULT_BANK_CREDIT_INCOME_TYPE_ID env; "other" credits require it explicitly.',
                            ),
                            new OA\Property(
                                property: 'accountId',
                                type: 'string',
                                description: 'Ledger Account UUID when known. If empty: DEFAULT_BANK_LEDGER_ACCOUNT_ID, then setup ledgerAccountId only if it is not classified as gas/auxiliary, then chart order (principal / current-account names first, gas-reserve-syndic last).',
                            ),
                            new OA\Property(property: 'dueDate', type: 'string', format: 'date', example: '2026-03-10'),
                            new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Conta de água março'),
                            new OA\Property(property: 'recurringExpenseId', type: 'string', format: 'uuid', nullable: true),
                            new OA\Property(
                                property: 'residentUnitId',
                                type: 'string',
                                format: 'uuid',
                                nullable: true,
                                description: 'Usually null for bank CREDIT lines.',
                            ),
                            new OA\Property(
                                property: 'isExpectedExpense',
                                type: 'boolean',
                                default: true,
                                description: 'Expense lines only. When true, also upserts expected expense memory for forecast.',
                            ),
                            new OA\Property(
                                property: 'expectedExpense',
                                type: 'object',
                                nullable: true,
                                description: 'recurringExpenseId and/or createOrUpdate when isExpectedExpense is true.',
                            ),
                        ],
                    ),
                ),
            ],
        ),
    ),
    tags: ['Bank / OFX'],
    responses: [
        new OA\Response(
            response: 201,
            description: 'Lines processed. Returns counters and the consolidated income id (if a settlement batch was created).',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'imported', type: 'integer', example: 10),
                    new OA\Property(property: 'expectedExpensesLinked', type: 'integer', example: 11),
                    new OA\Property(property: 'expectedExpensesCreated', type: 'integer', example: 2),
                    new OA\Property(
                        property: 'consolidatedIncomeId',
                        type: 'string',
                        format: 'uuid',
                        nullable: true,
                        description: 'Id of the single consolidated income built from all boleto_settlement lines (if any).',
                    ),
                    new OA\Property(
                        property: 'settlementMonth',
                        type: 'string',
                        nullable: true,
                        example: '2026-03',
                        description: 'Year-month (previous calendar month relative to latest postedAt) for the settlement batch.',
                    ),
                    new OA\Property(
                        property: 'settlementExpectedSlipTotalCents',
                        type: 'integer',
                        nullable: true,
                        example: 100000,
                        description: 'Sum of Slip amounts in that month from DB. Zero when no slips (greenfield); null if no settlement batch.',
                    ),
                    new OA\Property(
                        property: 'settlementValidatedAgainstSlips',
                        type: 'boolean',
                        nullable: true,
                        example: true,
                        description: 'True when slip total was positive and matched the bank sum. False when slip total was zero (bank amount accepted as initial).',
                    ),
                ],
            ),
        ),
        new OA\Response(
            response: 422,
            description: 'Boleto settlement mismatch: slip total for the month is positive but does not match the bank sum. Not used when expected slip total is zero (greenfield). No data was persisted.',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'boleto_settlement_mismatch'),
                    new OA\Property(property: 'message', type: 'string', example: 'Boleto settlement mismatch for 2026-03: expected 100000 cents, received 70000 cents (diff -30000).'),
                    new OA\Property(property: 'settlementMonth', type: 'string', example: '2026-03'),
                    new OA\Property(property: 'expectedCents', type: 'integer', example: 100000),
                    new OA\Property(property: 'receivedCents', type: 'integer', example: 70000),
                    new OA\Property(property: 'diffCents', type: 'integer', example: -30000),
                ],
            ),
        ),
    ],
)]
final class OfxConfirmPostController extends AbstractController
{
    public function __construct(
        private readonly ConfirmBankOfxLinesCommandHandler $handler,
        private readonly ExpenseRepository $expenseRepository,
        private readonly IncomeRepository $incomeRepository,
    ) {}

    public function __invoke(ConfirmBankOfxLinesRequestDto $request): JsonResponse
    {
        $lineDtos = array_map(
            static fn ($line) => new ConfirmLineDto(
                importLineKey: $line->importLineKey,
                amountInCents: $line->amountInCents,
                postedAt: $line->postedAt,
                memo: $line->memo,
                accountId: $line->accountId,
                dueDate: $line->dueDate,
                lineType: $line->lineType,
                expenseTypeId: $line->expenseTypeId,
                incomeTypeId: $line->incomeTypeId,
                description: $line->description,
                recurringExpenseId: $line->recurringExpenseId,
                residentUnitId: $line->residentUnitId,
                creditKind: $line->creditKind,
                isExpectedExpense: $line->isExpectedExpense,
                expectedExpense: $line->expectedExpense,
            ),
            $request->lines,
        );

        // Idempotency: generate deterministic IDs from importLineKey and filter already-imported lines.
        $existingIds = [];

        foreach ($lineDtos as $line) {
            $deterministicId = self::deterministicIdFromLineKey($line->importLineKey, $request->bankAccountId);

            if ($line->isIncome()) {
                $existingIds[$line->importLineKey] = $this->incomeRepository->findOneById($deterministicId);
            } else {
                $existingIds[$line->importLineKey] = $this->expenseRepository->findOneById($deterministicId);
            }
        }

        $filteredLines = array_values(array_filter(
            $lineDtos,
            static fn (ConfirmLineDto $l) => !isset($existingIds[$l->importLineKey]),
        ));

        // Attach deterministic IDs to filtered lines so the handler reuses them.
        $filteredLines = array_map(
            static fn (ConfirmLineDto $l) => new ConfirmLineDto(
                importLineKey: $l->importLineKey,
                amountInCents: $l->amountInCents,
                postedAt: $l->postedAt,
                memo: $l->memo,
                accountId: $l->accountId,
                dueDate: $l->dueDate,
                lineType: $l->lineType,
                expenseTypeId: $l->expenseTypeId,
                incomeTypeId: $l->incomeTypeId,
                description: $l->description,
                recurringExpenseId: $l->recurringExpenseId,
                residentUnitId: $l->residentUnitId,
                creditKind: $l->creditKind,
                isExpectedExpense: $l->isExpectedExpense,
                expectedExpense: $l->expectedExpense,
                deterministicId: self::deterministicIdFromLineKey($l->importLineKey, $request->bankAccountId),
            ),
            $filteredLines,
        );

        try {
            $result = ($this->handler)(new ConfirmBankOfxLinesCommand(
                bankAccountId: $request->bankAccountId,
                lines: $filteredLines,
                settlementExtraFeePerUnitCents: $request->settlementExtraFeePerUnitCents,
                settlementReserveFundPerUnitCents: $request->settlementReserveFundPerUnitCents,
            ));
        } catch (BoletoSettlementMismatchException $mismatch) {
            return new JsonResponse(
                ['error' => 'boleto_settlement_mismatch', 'message' => $mismatch->getMessage()]
                + $mismatch->toPayload(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return new JsonResponse([
            'recorded'                         => true,
            'imported'                         => $result->imported,
            'expectedExpensesLinked'           => $result->expectedExpensesLinked,
            'expectedExpensesCreated'          => $result->expectedExpensesCreated,
            'consolidatedIncomeId'             => $result->consolidatedIncomeId,
            'settlementMonth'                  => $result->settlementMonth,
            'settlementExpectedSlipTotalCents' => $result->settlementExpectedSlipTotalCents,
            'settlementValidatedAgainstSlips'  => $result->settlementValidatedAgainstSlips,
            'settlementSplitIncomeIds'         => $result->settlementSplitIncomeIds,
        ], Response::HTTP_CREATED);
    }

    /**
     * Generate a deterministic UUID v5-like ID from importLineKey + bankAccountId.
     */
    private static function deterministicIdFromLineKey(string $importLineKey, string $bankAccountId): string
    {
        $hash = hash('sha256', $importLineKey . '|' . $bankAccountId);
        // Build a UUID v4-like string from the first 32 hex chars
        $hex  = mb_substr($hash, 0, 32);

        return sprintf(
            '%s-%s-%s-%s-%s',
            mb_substr($hex, 0, 8),
            mb_substr($hex, 8, 4),
            '4' . mb_substr($hex, 13, 3), // version 4
            dechex((hexdec(mb_substr($hex, 16, 1)) & 0x3) | 0x8) . mb_substr($hex, 17, 3), // variant 10xx
            mb_substr($hex, 20, 12),
        );
    }
}
