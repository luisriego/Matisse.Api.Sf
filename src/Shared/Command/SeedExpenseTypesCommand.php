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
    private const EXPENSE_TYPES = [
        // Manutenção e Reparos (MR)
        'MR1GE' => ['name' => 'Manutenção Geral', 'description' => 'Pequenos reparos (hidráulica, elétrica em áreas comuns, chaveiro, etc.).', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR2EV' => ['name' => 'Manutenção do Elevador', 'description' => 'Contratos de manutenção, revisões periódicas, peças, reparos emergenciais.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'MR3JA' => ['name' => 'Jardinagem e Paisagismo', 'description' => 'Corte de grama, poda de árvores/arbustos, adubação, controle de pragas de jardim, irrigação.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR4PR' => ['name' => 'Manutenção Predial', 'description' => 'Pintura de áreas comuns (corredores, hall), reparos em alvenaria/pisos comuns, limpeza de fachada/garagem.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR5EQ' => ['name' => 'Manutenção de Equipamentos', 'description' => 'Bombas d\'água, portões eletrônicos, interfones, sistema de CFTV (câmeras).', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR6SI' => ['name' => 'Manutenção dos Sistemas de Incêndio', 'description' => 'Recarga/revisão de extintores, teste de mangueiras, manutenção de alarmes e detectores.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR7CP' => ['name' => 'Controle de Pragas', 'description' => 'Dedetizações periódicas ou emergenciais (baratas, ratos, cupins, etc.).', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR8RO' => ['name' => 'Manutenção Preventiva do Portão', 'description' => 'Manutenção preventiva ou paliativa do portão da garagem).', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],

        // Serviços Públicos / Contas de Consumo (SP)
        'SP1EL' => ['name' => 'CEMIG', 'description' => 'Conta de luz de corredores, elevador(es), bombas, portões, iluminação externa.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'SP2AG' => ['name' => 'COPASA', 'description' => 'Conta de água/esgoto para limpeza, jardinagem (se não individualizada), consumo da portaria.', 'distributionMethod' => 'FRACTION', 'isRecurring' => true],
        'SP3GA' => ['name' => 'Compra de Gás (Cilindro)', 'description' => 'Aquisição de gás em cilindro para áreas comuns ou uso condominial.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'SP4TC' => ['name' => 'Internet (CFTV)', 'description' => 'Linha telefônica/internet', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],

        // Pessoal / Folha de Pagamento (PF)
        'PF1SE' => ['name' => 'Salários e Encargos', 'description' => 'Taxa mensal do síndico', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'PF2SE' => ['name' => 'Rateio do Síndico', 'description' => 'Rateio mensal da taxa do síndico entre as unidades.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],

        // Serviços Terceirizados (ST)
        'ST1LT' => ['name' => 'Limpeza Terceirizada', 'description' => 'Contrato com empresa de limpeza.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'ST2AJ' => ['name' => 'Assessoria Jurídica/Contábil', 'description' => 'Honorários de advogados, contadores, auditorias.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],

        // Administrativo e Financeiro (AF)
        'AF1DB' => ['name' => 'Despesas Bancárias', 'description' => 'Tarifas de manutenção de conta, taxas de boletos.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'AF2SG' => ['name' => 'Seguros do Condomínio', 'description' => 'Seguro obrigatório do condomínio (incêndio, etc.), seguro de responsabilidade civil do síndico.', 'distributionMethod' => 'FRACTION', 'isRecurring' => false],
        'AF3ML' => ['name' => 'Material de Limpeza', 'description' => 'Produtos de limpeza, sacos de lixo', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'AF4IT' => ['name' => 'Impostos e Taxas', 'description' => 'IPTU de áreas comuns (se houver), outras taxas municipais/estaduais.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'AF5CC' => ['name' => 'Correios e Cartório', 'description' => 'Despesas com envio de correspondência, reconhecimento de firmas, cópias autenticadas.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],

        'OT1DA' => ['name' => 'Despesas da Assembleia', 'description' => 'Aluguel de espaço (se necessário), cópias de documentos, envio de convocações.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'OT2DD' => ['name' => 'Despesas Diversas', 'description' => 'Gastos menores e eventuais não classificáveis nas outras categorias.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
    ];
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

            $this->io->progressStart(count(self::EXPENSE_TYPES));

            foreach (self::EXPENSE_TYPES as $code => $data) {
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
