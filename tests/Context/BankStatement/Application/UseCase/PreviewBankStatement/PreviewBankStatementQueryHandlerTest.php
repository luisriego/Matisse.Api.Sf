<?php

declare(strict_types=1);

namespace App\Tests\Context\BankStatement\Application\UseCase\PreviewBankStatement;

use App\Context\BankStatement\Application\Matcher\EmbeddingCandidateDto;
use App\Context\BankStatement\Application\Matcher\EmbeddingMatcherInterface;
use App\Context\BankStatement\Application\Matcher\ExpenseHistoryMatcherInterface;
use App\Context\BankStatement\Application\UseCase\PreviewBankStatement\PreviewBankStatementQuery;
use App\Context\BankStatement\Application\UseCase\PreviewBankStatement\PreviewBankStatementQueryHandler;
use App\Context\BankStatement\Infrastructure\Ofx\OfxParser;
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
        self::assertSame('20260305001', $result->expenses[0]->fitId);
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

    // --- helpers ---

    /** @param EmbeddingCandidateDto[] $embeddingCandidates */
    private function buildHandler(array $embeddingCandidates): PreviewBankStatementQueryHandler
    {
        $parser = new OfxParser();

        $historyMatcher = $this->createMock(ExpenseHistoryMatcherInterface::class);
        $historyMatcher->method('match')->willReturn([
            'assignments' => [],
            'confidence'  => 0.0,
            'isNew'       => true,
        ]);
        $historyMatcher->method('isHighConfidence')->willReturn(false);

        $embeddingMatcher = $this->createMock(EmbeddingMatcherInterface::class);
        $embeddingMatcher->method('findSimilar')->willReturn($embeddingCandidates);

        return new PreviewBankStatementQueryHandler($parser, $historyMatcher, $embeddingMatcher);
    }
}
