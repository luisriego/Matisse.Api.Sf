<?php

declare(strict_types=1);

namespace App\Shared\Command;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_map;
use function count;
use function implode;
use function sprintf;

#[AsCommand(
    name: 'app:dev:reset-data',
    description: 'Dev utility to wipe data: all tables or only movement data.',
)]
final class ResetDevDataCommand extends Command
{
    private const SCOPE_ALL = 'all';
    private const SCOPE_MOVEMENTS = 'movements';

    /** @var list<string> */
    private const MOVEMENT_TABLES = [
        'bank_transaction_import',
        'expense_embedding',
        'slip_generation_parameter_snapshot',
        'slip',
        'expense',
        'recurring_expense',
        'income',
    ];

    /** @var list<string> */
    private const EVENT_STORE_KEEP_TYPES_FOR_MOVEMENTS_RESET = [
        // Keep gas setup history.
        'gas.price.was.defined',
        // Keep account base configuration and initial balance events.
        'account.initial_balance.set',
        // Opening reference month / demonstrative baseline for analysis and clients.
        'setup.opening_reference_month.was.recorded',
        // Condominium marked operational; never re-apply SETUP_REQUIRED after this.
        'setup.was.completed',
    ];

    public function __construct(
        private readonly Connection $connection,
        #[Autowire('%kernel.environment%')]
        private readonly string $appEnv,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'scope',
                null,
                InputOption::VALUE_REQUIRED,
                'Reset scope: "all" (wipe DB) or "movements" (keep users/accounts/types).',
                self::SCOPE_MOVEMENTS,
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Execute without interactive guard.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $scope = (string) $input->getOption('scope');
        $force = (bool) $input->getOption('force');

        if ($this->appEnv !== 'dev') {
            $io->error(sprintf(
                'This command is only allowed in APP_ENV=dev. Current environment: "%s".',
                $this->appEnv,
            ));

            return Command::FAILURE;
        }

        if ($scope !== self::SCOPE_ALL && $scope !== self::SCOPE_MOVEMENTS) {
            $io->error('Invalid --scope. Allowed values: all, movements.');

            return Command::INVALID;
        }

        if (!$force) {
            $io->warning('Safety guard: command requires --force to run.');
            $io->note('Example: php bin/console app:dev:reset-data --scope=movements --force');

            return Command::SUCCESS;
        }

        $io->title('Reset dev data');
        $io->text(sprintf('Scope: %s', $scope));

        if ($scope === self::SCOPE_ALL) {
            return $this->resetAll($io);
        }

        return $this->resetMovements($io);
    }

    private function resetAll(SymfonyStyle $io): int
    {
        /** @var list<string> $tables */
        $tables = $this->connection->fetchFirstColumn(
            "SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename <> 'doctrine_migration_versions' ORDER BY tablename",
        );

        if ($tables === []) {
            $io->success('No tables found to truncate.');

            return Command::SUCCESS;
        }

        $this->truncateTables($tables);

        $io->success(sprintf(
            'All data wiped. Truncated %d tables (keeping doctrine_migration_versions).',
            count($tables),
        ));

        return Command::SUCCESS;
    }

    private function resetMovements(SymfonyStyle $io): int
    {
        $existingMovementTables = $this->existingTables(self::MOVEMENT_TABLES);
        $missingMovementTables = array_values(array_diff(self::MOVEMENT_TABLES, $existingMovementTables));
        $eventStoreExists = $this->tableExists('event_store');

        if ($existingMovementTables !== []) {
            $this->truncateTables($existingMovementTables);
        }

        if ($eventStoreExists) {
            $this->connection->executeStatement(
                'DELETE FROM event_store WHERE event_type NOT IN (:types)',
                ['types' => self::EVENT_STORE_KEEP_TYPES_FOR_MOVEMENTS_RESET],
                ['types' => ArrayParameterType::STRING],
            );
        } else {
            $io->warning('Table "event_store" does not exist. Movement-related domain events were not deleted.');
        }

        if ($missingMovementTables !== []) {
            $io->note(sprintf(
                'Some movement tables do not exist in this schema and were skipped: %s',
                implode(', ', $missingMovementTables),
            ));
        }

        $io->success('Movement data reset completed: kept base event_store rows (gas, account initial balance, setup milestones); wiped movements tables and other events.');

        return Command::SUCCESS;
    }

    /**
     * @param list<string> $tables
     */
    private function truncateTables(array $tables): void
    {
        $quoted = array_map(
            static fn (string $table): string => '"' . $table . '"',
            $tables,
        );

        $sql = sprintf(
            'TRUNCATE TABLE %s RESTART IDENTITY CASCADE',
            implode(', ', $quoted),
        );

        $this->connection->executeStatement($sql);
    }

    /**
     * @param list<string> $tables
     *
     * @return list<string>
     */
    private function existingTables(array $tables): array
    {
        if ($tables === []) {
            return [];
        }

        /** @var list<string> $existing */
        $existing = $this->connection->fetchFirstColumn(
            "SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename IN (:tables)",
            ['tables' => $tables],
            ['tables' => ArrayParameterType::STRING],
        );

        return $existing;
    }

    private function tableExists(string $table): bool
    {
        $result = $this->connection->fetchOne(
            "SELECT EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = 'public' AND tablename = :table)",
            ['table' => $table],
        );

        return $result === true || $result === 't' || $result === '1' || $result === 1;
    }
}

