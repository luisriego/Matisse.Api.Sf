<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\EventSubscriber;

use App\Context\BankStatement\Application\Matcher\EmbeddingVectorClientInterface;
use App\Context\BankStatement\Domain\ExpenseEmbedding;
use App\Context\BankStatement\Domain\ExpenseEmbeddingRepository;
use App\Context\BankStatement\Infrastructure\Matcher\MemoFingerprint;
use App\Context\Expense\Domain\Event\ExpenseWasEntered;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\EventSubscriber;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Generates and stores an embedding for every new expense that has a description.
 * Failures are caught and logged — the primary expense creation is never blocked.
 */
final class IndexExpenseEmbeddingOnEntered implements EventSubscriber
{
    public function __construct(
        private readonly ExpenseEmbeddingRepository     $embeddingRepository,
        private readonly EmbeddingVectorClientInterface  $vectorClient,
        private readonly string                          $embeddingModel,
        private readonly LoggerInterface                 $logger,
    ) {}

    /** @param ExpenseWasEntered $event */
    public function __invoke(DomainEvent $event): void
    {
        $primitives  = $event->toPrimitives();
        $description = $primitives['description'] ?? null;

        if ($description === null || $description === '') {
            return;
        }

        try {
            $text   = MemoFingerprint::from($description);
            $vector = $this->vectorClient->embed($text);

            if ($vector === null) {
                $this->logger->warning('IndexExpenseEmbeddingOnEntered: Ollama returned null vector', [
                    'expenseId' => $event->aggregateId(),
                ]);

                return;
            }

            $this->embeddingRepository->upsert(new ExpenseEmbedding(
                id:             Uuid::v4()->toRfc4122(),
                expenseId:      $event->aggregateId(),
                vector:         $vector,
                description:    $description,
                embeddingModel: $this->embeddingModel,
            ));
        } catch (\Throwable $e) {
            $this->logger->error('IndexExpenseEmbeddingOnEntered: failed to index embedding', [
                'expenseId' => $event->aggregateId(),
                'error'     => $e->getMessage(),
            ]);
        }
    }

    public static function subscribedTo(): array
    {
        return [ExpenseWasEntered::class => '__invoke'];
    }
}
