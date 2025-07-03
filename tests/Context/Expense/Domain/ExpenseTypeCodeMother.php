<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\ExpenseTypeCode;

final class ExpenseTypeCodeMother
{
    private const CODES = [
        'MR1GE', 'MR2EV', 'MR3JA', 'MR4PR', 'MR5EQ', 'MR6SI', 'MR7CP', 'MR8RO',
        'SP1EL', 'SP2AG', 'SP3GA', 'SP4TC',
        'PF1SE',
        'ST1LT', 'ST2AJ',
        'AF1DB', 'AF2SG', 'AF3ML', 'AF4IT', 'AF5CC',
        'OT1DA', 'OT2DD',
    ];

    public static function create(?string $code = null): ExpenseTypeCode
    {
        $value = $code ?? self::randomCode();
        return new ExpenseTypeCode($value);
    }

    private static function randomCode(): string
    {
        return self::CODES[array_rand(self::CODES)];
    }
}
