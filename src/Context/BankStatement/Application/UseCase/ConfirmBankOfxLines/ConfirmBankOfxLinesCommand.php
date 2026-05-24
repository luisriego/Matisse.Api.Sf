<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines;

use App\Shared\Application\Command;

final readonly class ConfirmBankOfxLinesCommand implements Command
{
    /**
     * @param ConfirmLineDto[] $lines
     */
    public function __construct(
        public readonly string $bankAccountId,
        public readonly array $lines,
        /**
         * When no slip-generation snapshot exists for the expense month, use these values
         * to replay the same breakdown as slip generation (required if settlement income split is enabled).
         */
        public readonly ?int $settlementExtraFeePerUnitCents = null,
        public readonly ?int $settlementReserveFundPerUnitCents = null,
    ) {}
}
