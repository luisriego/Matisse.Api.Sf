<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\UseCase\PreviewBankStatement;

use App\Context\BankStatement\Application\Dto\BankStatementPreviewDto;
use App\Context\BankStatement\Application\Dto\TransactionPreviewDto;
use App\Context\BankStatement\Application\Matcher\EmbeddingMatcherInterface;
use App\Context\BankStatement\Application\Matcher\ExpenseHistoryMatcherInterface;
use App\Context\BankStatement\Application\Matcher\IncomeCreditHistoryMatcherInterface;
use App\Context\BankStatement\Domain\BankTransaction;
use App\Context\BankStatement\Infrastructure\Matcher\CreditMemoClassifier;
use App\Context\BankStatement\Infrastructure\Matcher\MemoFingerprint;
use App\Context\BankStatement\Infrastructure\Ofx\OfxParser;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Shared\Application\QueryHandler;
use DateMalformedStringException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use function min;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class PreviewBankStatementQueryHandler implements QueryHandler
{
    private const float CREDIT_PRE_FILL_THRESHOLD = 0.75;

    /** Minimum cosine similarity [0,1] to copy tipo/conta from a pgvector-matched expense. */
    private const float EMBEDDING_SUGGEST_MIN_SCORE = 0.78;

    /** Same bar as {@see ExpenseHistoryMatcherInterface} high confidence — marks preview pre_filled when embedding alone suffices. */
    private const float EMBEDDING_PRE_FILL_MIN_SCORE = 0.78;

    public function __construct(
        private OfxParser $ofxParser,
        private ExpenseHistoryMatcherInterface $historyMatcher,
        private EmbeddingMatcherInterface $embeddingMatcher,
        private ExpenseRepository $expenseRepository,
        private CreditMemoClassifier $creditMemoClassifier,
        private IncomeCreditHistoryMatcherInterface $incomeCreditHistoryMatcher,
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
            $preview = $this->buildCreditPreview($transaction);
            $creditPreviews[] = $preview;

            if ($preview->status === 'needs_review') {
                ++$needsReview;
            } else {
                ++$preFilled;
            }
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

        $top = $assignments[0] ?? null;

        // Semantic enrichment via Ollama embeddings (gracefully degrades to [] on failure)
        $embeddingCandidates = $this->embeddingMatcher->findSimilar(
            MemoFingerprint::from($transaction->memo),
        );

        $suggestedExpenseTypeId      = $top?->expenseTypeId;
        $suggestedExpenseTypeName      = $top?->expenseTypeName;
        $suggestedRecurringExpenseId   = $top?->recurringExpenseId;
        $suggestedAccountId            = $top?->accountId;
        $suggestedResidentUnitId       = $top?->residentUnitId;

        $fromEmbedding       = false;
        $bestEmbeddingScore  = 0.0;

        if ($embeddingCandidates !== []) {
            $best                 = $embeddingCandidates[0];
            $bestEmbeddingScore   = $best->score;
            if ($best->score >= self::EMBEDDING_SUGGEST_MIN_SCORE) {
                $matchedExpense = $this->expenseRepository->findOneById($best->candidateId);
                if (null !== $matchedExpense && null !== $matchedExpense->type()) {
                    $fromEmbedding = true;
                    if (null === $suggestedExpenseTypeId) {
                        $suggestedExpenseTypeId     = $matchedExpense->type()->id();
                        $suggestedExpenseTypeName   = $matchedExpense->type()->name();
                    }
                    if (null === $suggestedAccountId && null !== $matchedExpense->account()) {
                        $suggestedAccountId = $matchedExpense->account()->id();
                    }
                    if (null === $suggestedRecurringExpenseId && null !== $matchedExpense->recurringExpense()) {
                        $suggestedRecurringExpenseId = $matchedExpense->recurringExpense()->id();
                    }
                    if (null === $suggestedResidentUnitId && null !== $matchedExpense->residentUnitId()) {
                        $suggestedResidentUnitId = $matchedExpense->residentUnitId();
                    }
                }
            }
        }

        $historyHigh = !$isNew && $this->historyMatcher->isHighConfidence($confidence);
        $embeddingHigh = $fromEmbedding && $bestEmbeddingScore >= self::EMBEDDING_PRE_FILL_MIN_SCORE;
        $status        = ($historyHigh || $embeddingHigh) ? 'pre_filled' : 'needs_review';

        $displayConfidence = max($confidence, $fromEmbedding ? $bestEmbeddingScore : 0.0);
        $displayIsNew      = $isNew && !$fromEmbedding;

        return new TransactionPreviewDto(
            importLineKey: $transaction->importLineKey,
            bankAccountId: $transaction->bankAccountId,
            type: $transaction->type,
            amountInCents: $transaction->absAmountInCents(),
            postedAt: $transaction->postedAt->value(),
            memo: $transaction->memo,
            status: $status,
            isNew: $displayIsNew,
            confidence: round($displayConfidence, 2),
            pastAssignments: $assignments,
            suggestedExpenseTypeId: $suggestedExpenseTypeId,
            suggestedExpenseTypeName: $suggestedExpenseTypeName,
            suggestedRecurringExpenseId: $suggestedRecurringExpenseId,
            suggestedAccountId: $suggestedAccountId,
            suggestedResidentUnitId: $suggestedResidentUnitId,
            embeddingCandidates: $embeddingCandidates,
        );
    }

    private function buildCreditPreview(BankTransaction $transaction): TransactionPreviewDto
    {
        $memoHint = $this->creditMemoClassifier->classify($transaction->memo);
        $history  = $this->incomeCreditHistoryMatcher->match($transaction);

        $suggestedCreditKind            = $memoHint['creditKind'];
        $creditClassificationSource     = $memoHint['source'];
        $creditClassificationConfidence = $memoHint['confidence'];

        $pastIncomeAssignments = $history['assignments'];
        $suggestedIncomeTypeId = null;
        $suggestedIncomeTypeName = null;

        if ($pastIncomeAssignments !== []) {
            $top                   = $pastIncomeAssignments[0];
            $suggestedIncomeTypeId = $top->incomeTypeId;
            $suggestedIncomeTypeName = $top->incomeTypeName;
        }

        if ($suggestedCreditKind === null
            && !$history['isNew']
            && $history['confidence'] >= 0.55
            && $pastIncomeAssignments !== []
        ) {
            $topAssignment                = $pastIncomeAssignments[0];
            $suggestedCreditKind          = $topAssignment->inferredCreditKind;
            $creditClassificationSource   = 'income_history';
            $creditClassificationConfidence = min(0.85, $history['confidence']);
        }

        $isNew = $suggestedCreditKind === null && $history['isNew'];

        $status = ($suggestedCreditKind !== null && $creditClassificationConfidence >= self::CREDIT_PRE_FILL_THRESHOLD)
            ? 'pre_filled'
            : 'needs_review';

        $confidence = $creditClassificationConfidence > 0.0
            ? $creditClassificationConfidence
            : $history['confidence'];

        return new TransactionPreviewDto(
            importLineKey: $transaction->importLineKey,
            bankAccountId: $transaction->bankAccountId,
            type: $transaction->type,
            amountInCents: $transaction->absAmountInCents(),
            postedAt: $transaction->postedAt->value(),
            memo: $transaction->memo,
            status: $status,
            isNew: $isNew,
            confidence: round($confidence, 2),
            pastAssignments: [],
            suggestedExpenseTypeId: null,
            suggestedExpenseTypeName: null,
            suggestedRecurringExpenseId: null,
            suggestedAccountId: null,
            suggestedResidentUnitId: null,
            embeddingCandidates: [],
            suggestedCreditKind: $suggestedCreditKind,
            creditClassificationSource: $creditClassificationSource,
            creditClassificationConfidence: round($creditClassificationConfidence, 2),
            suggestedIncomeTypeId: $suggestedIncomeTypeId,
            suggestedIncomeTypeName: $suggestedIncomeTypeName,
            pastIncomeAssignments: $pastIncomeAssignments,
        );
    }
}
