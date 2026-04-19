<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines;

final readonly class ConfirmBankOfxLinesResult
{
    /**
     * @param string[] $skippedFitIds fitIds that were already imported (idempotency).
     */
    public function __construct(
        public readonly int     $imported,
        public readonly int     $skipped,
        public readonly array   $skippedFitIds,
        /**
         * Id of the consolidated income created from "boleto_settlement" lines (if any).
         * Null when there were no settlement lines or all of them were already imported.
         */
        public readonly ?string $consolidatedIncomeId = null,
        /** Year/month of the settlement (e.g. "2026-03"). Null when there is no consolidated income. */
        public readonly ?string $settlementMonth      = null,
        /**
         * Sum of Slip amounts in the settlement month from DB (0 when no slips / greenfield).
         * Null when this request did not process a boleto_settlement batch.
         */
        public readonly ?int $settlementExpectedSlipTotalCents = null,
        /**
         * True when expected slip total was positive and matched the bank sum (strict reconciliation).
         * False when expected was zero: bank amount accepted as initial income without slip check.
         */
        public readonly ?bool $settlementValidatedAgainstSlips = null,
    ) {}
}
