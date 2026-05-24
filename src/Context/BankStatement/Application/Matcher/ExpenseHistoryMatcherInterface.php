<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Matcher;

use App\Context\BankStatement\Application\Dto\PastAssignmentDto;
use App\Context\BankStatement\Domain\BankTransaction;

/**
 * Port for fingerprint / string-similarity matching against past expenses.
 */
interface ExpenseHistoryMatcherInterface
{
    /**
     * @return array{assignments: PastAssignmentDto[], confidence: float, isNew: bool}
     */
    public function match(BankTransaction $transaction): array;

    public function isHighConfidence(float $confidence): bool;
}
