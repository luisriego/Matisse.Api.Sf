<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Matcher;

use App\Context\BankStatement\Application\Dto\IncomePastAssignmentDto;
use App\Context\BankStatement\Domain\BankTransaction;

interface IncomeCreditHistoryMatcherInterface
{
    /**
     * @return array{
     *     assignments: IncomePastAssignmentDto[],
     *     confidence: float,
     *     isNew: bool
     * }
     */
    public function match(BankTransaction $transaction): array;
}
