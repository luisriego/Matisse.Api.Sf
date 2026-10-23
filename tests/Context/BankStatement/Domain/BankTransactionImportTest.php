<?php

declare(strict_types=1);

namespace App\Tests\Context\BankStatement\Domain;

use App\Context\BankStatement\Domain\BankTransactionImport;
use App\Shared\Domain\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BankTransactionImportTest extends TestCase
{
    private string $validId = 'b8e7f6a5-4c3d-2e1f-0a9b-8c7d6e5f4a3b';
    private string $validImportLineKey = 'fitid-001';
    private string $validBankAccountId = 'acc-123';

    public function testItCreatesWithExpenseIdOnly(): void
    {
        $import = new BankTransactionImport(
            $this->validId,
            $this->validImportLineKey,
            $this->validBankAccountId,
            'expense-uuid',
            null,
        );

        $this->assertSame('expense-uuid', $import->expenseId());
        $this->assertNull($import->incomeId());
    }

    public function testItCreatesWithIncomeIdOnly(): void
    {
        $import = new BankTransactionImport(
            $this->validId,
            $this->validImportLineKey,
            $this->validBankAccountId,
            null,
            'income-uuid',
        );

        $this->assertNull($import->expenseId());
        $this->assertSame('income-uuid', $import->incomeId());
    }

    public function testItThrowsWhenBothIdsAreNull(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('BankTransactionImport must reference exactly one of expenseId or incomeId.');

        new BankTransactionImport(
            $this->validId,
            $this->validImportLineKey,
            $this->validBankAccountId,
            null,
            null,
        );
    }

    public function testItThrowsWhenBothIdsAreProvided(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('BankTransactionImport must reference exactly one of expenseId or incomeId.');

        new BankTransactionImport(
            $this->validId,
            $this->validImportLineKey,
            $this->validBankAccountId,
            'expense-uuid',
            'income-uuid',
        );
    }
}
