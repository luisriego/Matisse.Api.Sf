<?php

declare(strict_types=1);

namespace App\Tests\Context\BankStatement\Application\UseCase\PreviewBankStatement;

use App\Context\BankStatement\Application\Dto\PastAssignmentDto;
use App\Context\BankStatement\Application\Matcher\EmbeddingCandidateDto;
use App\Context\BankStatement\Application\Matcher\EmbeddingMatcherInterface;
use App\Context\BankStatement\Application\Matcher\ExpenseHistoryMatcherInterface;
use App\Context\BankStatement\Application\Matcher\IncomeCreditHistoryMatcherInterface;
use App\Context\BankStatement\Application\Service\ExpectedExpensePreviewSuggester;
use App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines\ConfirmLineDto;
use App\Context\BankStatement\Application\UseCase\PreviewBankStatement\PreviewBankStatementQuery;
use App\Context\BankStatement\Application\UseCase\PreviewBankStatement\PreviewBankStatementQueryHandler;
use App\Context\BankStatement\Infrastructure\Matcher\CreditMemoClassifier;
use App\Context\BankStatement\Infrastructure\Ofx\OfxParser;
use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Forecast\Domain\Service\ExpectedExpenseFrequencyInferrer;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use App\Tests\Context\Expense\Domain\RecurringExpenseMother;
use PHPUnit\Framework\TestCase;

final class PreviewBankStatementQueryHandlerTest extends TestCase
{
    private const string MINIMAL_OFX = <<<OFX
        OFXHEADER:100
        DATA:OFXSGML
        VERSION:102
        CHARSET:1252
        ENCODING:UTF-8

        <OFX>
        <BANKMSGSRSV1>
        <STMTTRNRS>
        <STMTRS>
        <CURDEF>BRL</CURDEF>
        <BANKACCTFROM>
        <BANKID>0341</BANKID>
        <ACCTID>9999999</ACCTID>
        </BANKACCTFROM>
        <BANKTRANLIST>
        <DTSTART>20260301</DTSTART>
        <DTEND>20260331</DTEND>
        <STMTTRN>
        <TRNTYPE>DEBIT</TRNTYPE>
        <DTPOSTED>20260305</DTPOSTED>
        <TRNAMT>-150.00</TRNAMT>
        <FITID>20260305001</FITID>
        <MEMO>DA COPASA 000123</MEMO>
        </STMTTRN>
        <STMTTRN>
        <TRNTYPE>CREDIT</TRNTYPE>
        <DTPOSTED>20260310</DTPOSTED>
        <TRNAMT>500.00</TRNAMT>
        <FITID>20260310001</FITID>
        <MEMO>BOLETOS RECEBIDOS</MEMO>
        </STMTTRN>
        </BANKTRANLIST>
        </STMTRS>
        </STMTTRNRS>
        </BANKMSGSRSV1>
        </OFX>
        OFX;

    public function test_it_parses_expenses_and_credits_correctly(): void
    {
        $handler = $this->buildHandler(embeddingCandidates: []);

        $result = $handler(new PreviewBankStatementQuery(self::MINIMAL_OFX));

        self::assertCount(1, $result->expenses);
        self::assertCount(1, $result->credits);
        self::assertSame('20260305001', $result->expenses[0]->importLineKey);
        self::assertSame(15000, $result->expenses[0]->amountInCents);
        self::assertSame('2026-03-05', $result->expenses[0]->postedAt);
        self::assertSame('needs_review', $result->expenses[0]->status);
        self::assertTrue($result->expenses[0]->isNew);
    }

    public function test_it_attaches_embedding_candidates_to_expense_lines(): void
    {
        $candidate = new EmbeddingCandidateDto(
            candidateId: 'expense-uuid-1',
            label: 'COPASA água mensal',
            score: 0.92,
            embeddingModel: 'nomic-embed-text',
        );

        $handler = $this->buildHandler(embeddingCandidates: [$candidate]);

        $result = $handler(new PreviewBankStatementQuery(self::MINIMAL_OFX));

        $expense = $result->expenses[0];
        self::assertCount(1, $expense->embeddingCandidates);
        self::assertSame('expense-uuid-1', $expense->embeddingCandidates[0]->candidateId);
        self::assertSame(0.92, $expense->embeddingCandidates[0]->score);
    }

    public function test_it_attaches_empty_candidates_when_embedding_service_unavailable(): void
    {
        $handler = $this->buildHandler(embeddingCandidates: []);

        $result = $handler(new PreviewBankStatementQuery(self::MINIMAL_OFX));

        self::assertSame([], $result->expenses[0]->embeddingCandidates);
    }

    public function test_it_does_not_attach_embedding_candidates_to_credit_lines(): void
    {
        $candidate = new EmbeddingCandidateDto('uuid', 'label', 0.9, 'model');

        $handler = $this->buildHandler(embeddingCandidates: [$candidate]);

        $result = $handler(new PreviewBankStatementQuery(self::MINIMAL_OFX));

        // Credit lines do not go through the embedding matcher
        self::assertSame([], $result->credits[0]->embeddingCandidates);
    }

    public function test_credit_preview_classifies_boleto_memo_as_settlement(): void
    {
        $handler = $this->buildHandler(embeddingCandidates: []);

        $result = $handler(new PreviewBankStatementQuery(self::MINIMAL_OFX));

        $credit = $result->credits[0];
        self::assertSame(ConfirmLineDto::CREDIT_KIND_BOLETO_SETTLEMENT, $credit->suggestedCreditKind);
        self::assertSame('pre_filled', $credit->status);
        self::assertGreaterThanOrEqual(0.75, $credit->creditClassificationConfidence);
        self::assertStringStartsWith('memo_pattern:settlement:', (string) $credit->creditClassificationSource);
    }

    public function test_credit_preview_classifies_yield_memo_as_other(): void
    {
        $ofx = str_replace(
            '<MEMO>BOLETOS RECEBIDOS</MEMO>',
            '<MEMO>RENDIMENTOS REND PAGO APLIC AUT MAIS</MEMO>',
            self::MINIMAL_OFX,
        );

        $handler = $this->buildHandler(embeddingCandidates: []);

        $result = $handler(new PreviewBankStatementQuery($ofx));

        $credit = $result->credits[0];
        self::assertSame(ConfirmLineDto::CREDIT_KIND_OTHER, $credit->suggestedCreditKind);
        self::assertSame('pre_filled', $credit->status);
        self::assertStringStartsWith('memo_pattern:other:', (string) $credit->creditClassificationSource);
    }

    public function test_debit_preview_hydrates_suggested_fields_from_top_embedding_match(): void
    {
        $matched = ExpenseMother::create(id: 'expense-uuid-1');

        $candidate = new EmbeddingCandidateDto(
            candidateId: 'expense-uuid-1',
            label:       'COPASA água mensal',
            score:       0.92,
            embeddingModel: 'nomic-embed-text',
        );

        $handler = $this->buildHandler(embeddingCandidates: [$candidate], embeddingMatchedExpense: $matched);

        $result = $handler(new PreviewBankStatementQuery(self::MINIMAL_OFX));

        $expensePreview = $result->expenses[0];
        self::assertSame($matched->type()->id(), $expensePreview->suggestedExpenseTypeId);
        self::assertSame($matched->account()->id(), $expensePreview->suggestedAccountId);
        self::assertSame('pre_filled', $expensePreview->status);
        self::assertFalse($expensePreview->isNew);
        self::assertGreaterThanOrEqual(0.92, $expensePreview->confidence);
    }

    public function test_debit_preview_does_not_hydrate_from_embedding_when_score_below_threshold(): void
    {
        $matched = ExpenseMother::create(id: 'expense-uuid-1');
        $candidate = new EmbeddingCandidateDto(
            candidateId: 'expense-uuid-1',
            label:       'weak',
            score:       0.50,
            embeddingModel: 'nomic-embed-text',
        );

        $handler = $this->buildHandler(embeddingCandidates: [$candidate], embeddingMatchedExpense: $matched);

        $result = $handler(new PreviewBankStatementQuery(self::MINIMAL_OFX));

        $expensePreview = $result->expenses[0];
        self::assertNull($expensePreview->suggestedExpenseTypeId);
        self::assertNull($expensePreview->suggestedAccountId);
        self::assertSame('needs_review', $expensePreview->status);
    }

    public function test_debit_preview_suggests_expected_expense_from_memo_when_no_recurring(): void
    {
        $handler = $this->buildHandler(embeddingCandidates: []);

        $result = $handler(new PreviewBankStatementQuery(self::MINIMAL_OFX));

        $expense = $result->expenses[0];
        self::assertTrue($expense->suggestedIsExpectedExpense);
        self::assertNotNull($expense->suggestedExpectedExpense);
        self::assertNull($expense->suggestedExpectedExpense['recurringExpenseId']);
        self::assertSame('COPASA', $expense->suggestedExpectedExpense['createOrUpdate']['displayName']);
        self::assertSame('monthly', $expense->suggestedExpectedExpense['createOrUpdate']['frequency']);
        self::assertSame('variable', $expense->suggestedExpectedExpense['createOrUpdate']['amountKind']);
        self::assertSame(5, $expense->suggestedExpectedExpense['createOrUpdate']['dueDay']);
    }

    public function test_debit_preview_suggests_expected_expense_from_existing_recurring(): void
    {
        $recurring = RecurringExpenseMother::create(description: 'Copasa mensual');

        $assignment = new PastAssignmentDto(
            month: 2,
            year: 2026,
            amountInCents: 15000,
            expenseTypeId: 'type-id',
            expenseTypeName: 'Água',
            recurringExpenseId: $recurring->id(),
            recurringExpenseName: 'Copasa mensual',
            accountId: 'acc-id',
            residentUnitId: null,
            confidence: 0.9,
        );

        $handler = $this->buildHandler(
            embeddingCandidates: [],
            recurringExpense: $recurring,
            historyAssignments: [$assignment],
        );

        $result = $handler(new PreviewBankStatementQuery(self::MINIMAL_OFX));

        $expense = $result->expenses[0];
        self::assertSame($recurring->id(), $expense->suggestedExpectedExpense['recurringExpenseId']);
        self::assertSame('Copasa mensual', $expense->suggestedExpectedExpense['createOrUpdate']['displayName']);
    }

    public function test_credit_preview_does_not_suggest_expected_expense(): void
    {
        $handler = $this->buildHandler(embeddingCandidates: []);

        $result = $handler(new PreviewBankStatementQuery(self::MINIMAL_OFX));

        $credit = $result->credits[0];
        self::assertFalse($credit->suggestedIsExpectedExpense);
        self::assertNull($credit->suggestedExpectedExpense);
    }

    // --- helpers ---

    /**
     * @param EmbeddingCandidateDto[] $embeddingCandidates
     * @param PastAssignmentDto[]     $historyAssignments
     */
    private function buildHandler(
        array $embeddingCandidates,
        ?Expense $embeddingMatchedExpense = null,
        ?RecurringExpense $recurringExpense = null,
        array $historyAssignments = [],
    ): PreviewBankStatementQueryHandler {
        $parser = new OfxParser();

        $historyMatcher = $this->createMock(ExpenseHistoryMatcherInterface::class);
        $historyMatcher->method('match')->willReturn([
            'assignments' => $historyAssignments,
            'confidence'  => $historyAssignments === [] ? 0.0 : 0.9,
            'isNew'       => $historyAssignments === [],
        ]);
        $historyMatcher->method('isHighConfidence')->willReturn($historyAssignments !== []);

        $embeddingMatcher = $this->createMock(EmbeddingMatcherInterface::class);
        $embeddingMatcher->method('findSimilar')->willReturn($embeddingCandidates);

        $expenseRepository = $this->createMock(ExpenseRepository::class);
        if ($embeddingMatchedExpense !== null) {
            $expenseRepository->method('findOneById')->willReturnCallback(
                static fn (string $id): ?Expense => $id === $embeddingMatchedExpense->id() ? $embeddingMatchedExpense : null,
            );
        } else {
            $expenseRepository->method('findOneById')->willReturn(null);
        }

        $incomeCreditMatcher = $this->createMock(IncomeCreditHistoryMatcherInterface::class);
        $incomeCreditMatcher->method('match')->willReturn([
            'assignments' => [],
            'confidence'  => 0.0,
            'isNew'       => true,
        ]);

        $recurringRepo = $this->createMock(RecurringExpenseRepository::class);
        if ($recurringExpense !== null) {
            $recurringRepo->method('findOneByIdOrFail')->willReturn($recurringExpense);
        }

        $expectedExpenseSuggester = new ExpectedExpensePreviewSuggester(
            $recurringRepo,
            new ExpectedExpenseFrequencyInferrer(),
        );

        return new PreviewBankStatementQueryHandler(
            $parser,
            $historyMatcher,
            $embeddingMatcher,
            $expenseRepository,
            new CreditMemoClassifier(),
            $incomeCreditMatcher,
            $expectedExpenseSuggester,
        );
    }
}
