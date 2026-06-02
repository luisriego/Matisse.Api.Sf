<?php

declare(strict_types=1);

namespace App\Tests\Context\BankStatement\Application\Service;

use App\Context\BankStatement\Application\Service\ExpectedExpensePreviewSuggester;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDay;
use App\Context\Forecast\Domain\Service\ExpectedExpenseFrequencyInferrer;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Tests\Context\Expense\Domain\ExpenseIdMother;
use App\Tests\Context\Expense\Domain\RecurringExpenseMother;
use PHPUnit\Framework\TestCase;

use function range;

final class ExpectedExpensePreviewSuggesterTest extends TestCase
{
    private ExpectedExpensePreviewSuggester $suggester;

    protected function setUp(): void
    {
        $recurringRepo = $this->createMock(RecurringExpenseRepository::class);
        $this->suggester = new ExpectedExpensePreviewSuggester(
            $recurringRepo,
            new ExpectedExpenseFrequencyInferrer(),
        );
    }

    public function testItSuggestsCreateOrUpdateFromMemoWhenNoRecurring(): void
    {
        $preview = $this->suggester->suggestForDebit(null, 'DA COPASA 000123', '2026-03-05');

        self::assertNull($preview->recurringExpenseId);
        self::assertNotNull($preview->createOrUpdate);
        self::assertSame('COPASA', $preview->createOrUpdate->displayName);
        self::assertSame('monthly', $preview->createOrUpdate->frequency);
        self::assertSame('variable', $preview->createOrUpdate->amountKind);
        self::assertSame(5, $preview->createOrUpdate->dueDay);
    }

    public function testItStripsBoletoPagoPrefixFromMemo(): void
    {
        $preview = $this->suggester->suggestForDebit(null, 'BOLETO PAGO CEMIG 123456', '2026-01-15');

        self::assertSame('CEMIG', $preview->createOrUpdate?->displayName);
        self::assertSame(15, $preview->createOrUpdate?->dueDay);
    }

    public function testItBuildsPreviewFromExistingRecurring(): void
    {
        $recurringId = ExpenseIdMother::create()->value();
        $recurring = RecurringExpenseMother::create(
            id: ExpenseIdMother::create($recurringId),
            dueDay: new ExpenseDueDay(10),
            monthsOfYear: range(1, 12),
            description: 'Copasa mensual',
        );

        $recurringRepo = $this->createMock(RecurringExpenseRepository::class);
        $recurringRepo->method('findOneByIdOrFail')->with($recurringId)->willReturn($recurring);

        $suggester = new ExpectedExpensePreviewSuggester(
            $recurringRepo,
            new ExpectedExpenseFrequencyInferrer(),
        );

        $preview = $suggester->suggestForDebit($recurringId, 'DA COPASA', '2026-03-05');

        self::assertSame($recurringId, $preview->recurringExpenseId);
        self::assertNotNull($preview->createOrUpdate);
        self::assertSame('Copasa mensual', $preview->createOrUpdate->displayName);
        self::assertSame('monthly', $preview->createOrUpdate->frequency);
        self::assertSame('fixed', $preview->createOrUpdate->amountKind);
        self::assertSame(10, $preview->createOrUpdate->dueDay);
    }

    public function testItIncludesCustomMonthsWhenRecurringIsNotMonthly(): void
    {
        $recurringId = ExpenseIdMother::create()->value();
        $recurring = RecurringExpenseMother::create(
            id: ExpenseIdMother::create($recurringId),
            monthsOfYear: [1, 4, 7, 10],
            description: 'Trimestral',
        );

        $recurringRepo = $this->createMock(RecurringExpenseRepository::class);
        $recurringRepo->method('findOneByIdOrFail')->willReturn($recurring);

        $suggester = new ExpectedExpensePreviewSuggester(
            $recurringRepo,
            new ExpectedExpenseFrequencyInferrer(),
        );

        $preview = $suggester->suggestForDebit($recurringId, 'memo', '2026-03-05');

        self::assertSame('custom', $preview->createOrUpdate?->frequency);
        self::assertSame([1, 4, 7, 10], $preview->createOrUpdate?->monthsOfYear);
    }

    public function testItReturnsRecurringIdOnlyWhenRecurringNotFound(): void
    {
        $recurringId = ExpenseIdMother::create()->value();

        $recurringRepo = $this->createMock(RecurringExpenseRepository::class);
        $recurringRepo->method('findOneByIdOrFail')->willThrowException(
            ResourceNotFoundException::createFromClassAndId('RecurringExpense', $recurringId),
        );

        $suggester = new ExpectedExpensePreviewSuggester(
            $recurringRepo,
            new ExpectedExpenseFrequencyInferrer(),
        );

        $preview = $suggester->suggestForDebit($recurringId, 'memo', '2026-03-05');

        self::assertSame($recurringId, $preview->recurringExpenseId);
        self::assertNull($preview->createOrUpdate);
    }
}
