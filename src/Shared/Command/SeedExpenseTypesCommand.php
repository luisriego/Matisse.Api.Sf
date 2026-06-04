<?php

declare(strict_types=1);

namespace App\Shared\Command;

use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\ExpenseTypeRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeCode;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeDescription;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeDistributionMethod;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeId;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeName;
use App\Shared\Domain\Catalog\ExpenseTypeCatalog;
use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\DBAL\Connection;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function count;

#[AsCommand(
    name: 'app:seed:expense-types',
    description: 'Seeds the database with initial expense types. This command is idempotent.',
)]
final class SeedExpenseTypesCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly ExpenseTypeRepository $repository,
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('all', null, InputOption::VALUE_NONE, 'Create **all** predefined expense types in one go')
            ->addArgument('id', InputArgument::OPTIONAL, 'UUID for the new expense type')
            ->addArgument('code', InputArgument::OPTIONAL, 'Unique code (max 10 chars)')
            ->addArgument('name', InputArgument::OPTIONAL, 'Name of the expense type')
            ->addArgument('distributionMethod', InputArgument::OPTIONAL, 'Distribution method: EQUAL, FRACTION or INDIVIDUAL')
            ->addArgument('description', InputArgument::OPTIONAL, 'Optional description')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite if the same id already exists')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getOption('all')) {
            $this->io->warning('This command only runs with the --all option to prevent accidental execution.');
            $this->io->note('Please run the command with --all to seed the expense types.');

            return Command::SUCCESS;
        }

        $this->io->title('Seeding all predefined ExpenseTypes…');

        try {
            $this->io->text('Truncating expense_type table...');
            $this->connection->executeStatement('TRUNCATE TABLE expense_type RESTART IDENTITY CASCADE');

            $this->io->progressStart(count(ExpenseTypeCatalog::TYPES));

            foreach (ExpenseTypeCatalog::TYPES as $code => $data) {
                $id   = new ExpenseTypeId((string) Uuid::random());
                $type = ExpenseType::create(
                    $id,
                    new ExpenseTypeCode($code),
                    new ExpenseTypeName($data['name']),
                    new ExpenseTypeDistributionMethod($data['distributionMethod']),
                    new ExpenseTypeDescription($data['description']),
                );
                $this->repository->save($type, true);
                $this->io->progressAdvance();
            }
            $this->io->progressFinish();
        } catch (Exception $e) {
            $this->io->error('An error occurred while seeding expense types: ' . $e->getMessage());

            return Command::FAILURE;
        }

        $this->io->success('All expense types have been successfully seeded.');

        return Command::SUCCESS;
    }
}
