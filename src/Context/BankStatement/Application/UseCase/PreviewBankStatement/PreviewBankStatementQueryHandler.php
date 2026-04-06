<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\UseCase\PreviewBankStatement;

use App\Context\BankStatement\Application\Dto\BankStatementPreviewDto;
use App\Context\BankStatement\Application\Dto\TransactionPreviewDto;
use App\Context\BankStatement\Application\Matcher\EmbeddingMatcherInterface;
use App\Context\BankStatement\Application\Matcher\ExpenseHistoryMatcherInterface;
use App\Context\BankStatement\Domain\BankTransaction;
use App\Context\BankStatement\Infrastructure\Matcher\MemoFingerprint;
use App\Context\BankStatement\Infrastructure\Ofx\OfxParser;
use App\Shared\Application\QueryHandler;
use DateMalformedStringException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class PreviewBankStatementQueryHandler implements QueryHandler
{
    public function __construct(
        private OfxParser $ofxParser,
        private ExpenseHistoryMatcherInterface $historyMatcher,
        private EmbeddingMatcherInterface $embeddingMatcher,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(PreviewBankStatementQuery $query): BankStatementPreviewDto
    {
        $statement = $this->ofxParser->parse($query->ofxContent);

        $expensePreviews = [];
        $creditPreviews  = [];
        $needsReview     = 0;
        $preFilled       = 0;

        foreach ($statement->debits() as $transaction) {
            $preview = $this->buildPreview($transaction);
            $expensePreviews[] = $preview;

            if ($preview->status === 'needs_review') {
                ++$needsReview;
            } else {
                ++$preFilled;
            }
        }

        foreach ($statement->credits() as $transaction) {
            // Credits go to review as income lines; no history matching yet in Phase 1
            $creditPreviews[] = $this->buildCreditPreview($transaction);
            ++$needsReview;
        }

        return new BankStatementPreviewDto(
            bankId: $statement->bankId,
            accountId: $statement->accountId,
            currency: $statement->currency,
            periodStart: $statement->periodStart->value(),
            periodEnd: $statement->periodEnd->value(),
            ledgerBalanceInCents: $statement->ledgerBalanceInCents,
            ledgerBalanceDate: $statement->ledgerBalanceDate?->value(),
            expenses: $expensePreviews,
            credits: $creditPreviews,
            totalNeedsReview: $needsReview,
            totalPreFilled: $preFilled,
        );
    }

    private function buildPreview(BankTransaction $transaction): TransactionPreviewDto
    {
        $result = $this->historyMatcher->match($transaction);

        $isNew       = $result['isNew'];
        $confidence  = $result['confidence'];
        $assignments = $result['assignments'];

        // Rule: new entries ALWAYS need review; pre-fill only when high confidence AND has history
        $isHighConfidence = !$isNew && $this->historyMatcher->isHighConfidence($confidence);
        $status           = $isHighConfidence ? 'pre_filled' : 'needs_review';

        $top = $assignments[0] ?? null;

        // Semantic enrichment via Ollama embeddings (gracefully degrades to [] on failure)
        $embeddingCandidates = $this->embeddingMatcher->findSimilar(
            MemoFingerprint::from($transaction->memo),
        );

        return new TransactionPreviewDto(
            fitId: $transaction->fitId,
            bankAccountId: $transaction->bankAccountId,
            type: $transaction->type,
            amountInCents: $transaction->absAmountInCents(),
            postedAt: $transaction->postedAt->value(),
            memo: $transaction->memo,
            status: $status,
            isNew: $isNew,
            confidence: $confidence,
            pastAssignments: $assignments,
            suggestedExpenseTypeId: $top?->expenseTypeId,
            suggestedExpenseTypeName: $top?->expenseTypeName,
            suggestedRecurringExpenseId: $top?->recurringExpenseId,
            suggestedAccountId: $top?->accountId,
            suggestedResidentUnitId: $top?->residentUnitId,
            embeddingCandidates: $embeddingCandidates,
        );
    }

    private function buildCreditPreview(BankTransaction $transaction): TransactionPreviewDto
    {
        return new TransactionPreviewDto(
            fitId: $transaction->fitId,
            bankAccountId: $transaction->bankAccountId,
            type: $transaction->type,
            amountInCents: $transaction->absAmountInCents(),
            postedAt: $transaction->postedAt->value(),
            memo: $transaction->memo,
            status: 'needs_review',
            isNew: true,
            confidence: 0.0,
            pastAssignments: [],
            suggestedExpenseTypeId: null,
            suggestedExpenseTypeName: null,
            suggestedRecurringExpenseId: null,
            suggestedAccountId: null,
            suggestedResidentUnitId: null,
        );
    }
}
