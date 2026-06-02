<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Console;

use App\Context\BankStatement\Application\Matcher\EmbeddingVectorClientInterface;
use App\Context\BankStatement\Domain\ExpenseEmbedding;
use App\Context\BankStatement\Domain\ExpenseEmbeddingRepository;
use App\Context\BankStatement\Infrastructure\Matcher\MemoFingerprint;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Shared\Domain\ValueObject\DateRange;
use DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

use function count;
use function sprintf;

#[AsCommand(
    name: 'app:embeddings:reindex',
    description: 'Batch-index expense descriptions into the expense_embedding table (pgvector).',
)]
final class ReindexExpenseEmbeddingsCommand extends Command
{
    public function __construct(
        private readonly ExpenseRepository $expenseRepository,
        private readonly ExpenseEmbeddingRepository $embeddingRepository,
        private readonly EmbeddingVectorClientInterface $vectorClient,
        private readonly string $embeddingModel,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('months', null, InputOption::VALUE_OPTIONAL, 'How many months back to index', 24);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $months = (int) $input->getOption('months');

        $endDate   = new DateTime();
        $startDate = (clone $endDate)->modify(sprintf('-%d months', $months));
        $dateRange = new DateRange($startDate, $endDate);

        $expenses = $this->expenseRepository->findActiveByDateRange($dateRange);

        $io->title(sprintf('Re-indexing embeddings for %d expenses (last %d months)', count($expenses), $months));

        $indexed = 0;
        $skipped = 0;

        foreach ($expenses as $expense) {
            $description = $expense->description();

            if ($description === null || $description === '') {
                ++$skipped;

                continue;
            }

            $text   = MemoFingerprint::from($description);
            $vector = $this->vectorClient->embed($text);

            if ($vector === null) {
                $io->warning(sprintf('  Ollama failed for expense %s — skipped.', $expense->id()));
                ++$skipped;

                continue;
            }

            $this->embeddingRepository->upsert(new ExpenseEmbedding(
                id: Uuid::v4()->toRfc4122(),
                expenseId: $expense->id(),
                vector: $vector,
                description: $description,
                embeddingModel: $this->embeddingModel,
            ));

            ++$indexed;
            $io->writeln(sprintf('  [OK] %s', $expense->id()), OutputInterface::VERBOSITY_VERBOSE);
        }

        $io->success(sprintf('Done. Indexed: %d  |  Skipped (no description / Ollama error): %d', $indexed, $skipped));

        return Command::SUCCESS;
    }
}
