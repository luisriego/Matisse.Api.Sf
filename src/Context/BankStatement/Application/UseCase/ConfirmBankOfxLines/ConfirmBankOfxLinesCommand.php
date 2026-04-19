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
        public readonly array  $lines,
    ) {}
}
