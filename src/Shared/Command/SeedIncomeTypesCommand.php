<?php

declare(strict_types=1);

namespace App\Shared\Command;

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
    private const INCOMING_TYPES = [
        // Receitas Ordinárias
        'RC1TC' => ['name' => 'Taxa Condominial', 'description' => 'Recebimento da cota condominial mensal regular dos condôminos.'],
        'RC2JM' => ['name' => 'Juros e Multas por Atraso', 'description' => 'Recebimento de juros e multas por pagamento de cotas condominiais em atraso.'],

        // Receitas Extraordinárias
        'RC3AE' => ['name' => 'Aluguel de Espaços Comuns', 'description' => 'Receita proveniente do aluguel de espaços comuns (ex: salão de festas, churrasqueira).'],
        'RC4CE' => ['name' => 'Cota Extra', 'description' => 'Recebimento de cotas extras aprovadas em assembleia para fins específicos (obras, melhorias, fundo específico).'],
        'RC5RB' => ['name' => 'Reembolsos', 'description' => 'Recebimento de valores para cobrir danos causados por condôminos/terceiros, ressarcimento de despesas adiantadas, etc.'],

        // Receitas Financeiras
        'RC6RF' => ['name' => 'Rendimentos Financeiros', 'description' => 'Juros ou rendimentos de aplicações financeiras do fundo de reserva ou outras contas de investimento do condomínio.'],

        // Outras Receitas
        'RC7DV' => ['name' => 'Receitas Diversas', 'description' => 'Outras receitas eventuais não classificadas nas categorias anteriores (ex: venda de materiais recicláveis, multas não relacionadas a atraso de cota, doações).'],
    ];

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

            $io->progressStart(count(self::INCOMING_TYPES));

            foreach (self::INCOMING_TYPES as $code => $data) {
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
