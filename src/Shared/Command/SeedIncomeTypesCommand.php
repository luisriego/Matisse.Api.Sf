<?php

declare(strict_types=1);

namespace App\Shared\Command;

use App\Shared\Domain\Catalog\IncomeTypeCatalog;
use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\DBAL\Connection;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function count;

#[AsCommand(
    name: 'app:seed:income-types',
    description: 'Seeds the database with initial income types. This command is idempotent.',
)]
class SeedIncomeTypesCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('all', null, InputOption::VALUE_NONE, 'Create **all** predefined income types in one go');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('all')) {
            $io->warning('This command will not run without the --all option to prevent accidental execution.');
            $io->note('Please run the command with --all to seed the income types.');

            return Command::SUCCESS;
        }

        $io->title('Seeding Income Types...');

        try {
            $io->text('Truncating income_type table...');
            $this->connection->executeStatement('TRUNCATE TABLE income_type RESTART IDENTITY CASCADE');

            $io->progressStart(count(IncomeTypeCatalog::TYPES));

            foreach (IncomeTypeCatalog::TYPES as $code => $data) {
                $this->connection->insert('income_type', [
                    'id' => Uuid::random()->value(),
                    'name' => $data['name'],
                    'code' => $code,
                    'description' => $data['description'],
                ]);
                $io->progressAdvance();
            }
            $io->progressFinish();
        } catch (Exception $e) {
            $io->error('An error occurred while seeding income types: ' . $e->getMessage());

            return Command::FAILURE;
        }

        $io->success('Successfully seeded income types!');

        return Command::SUCCESS;
    }
}
