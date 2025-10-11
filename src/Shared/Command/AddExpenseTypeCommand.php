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
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\ValueObject\Uuid;
use http\Exception\RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function in_array;
use function sprintf;
use function trim;

#[AsCommand(
    name: 'app:add-expense-type',
    description: 'Creates a new ExpenseType and stores it in the database',
)]
final class AddExpenseTypeCommand extends Command
{
    private const EXPENSE_TYPES = [
        // Manutenção e Reparos (MR)
        'MR1GE' => ['name' => 'MANUTENCAO_GERAL', 'description' => 'Pequenos reparos (hidráulica, elétrica em áreas comuns, chaveiro, etc.).', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR2EV' => ['name' => 'MANUTENCAO_ELEVADOR', 'description' => 'Contratos de manutenção, revisões periódicas, peças, reparos emergenciais.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'MR3JA' => ['name' => 'JARDINAGEM_PAISAGISMO', 'description' => 'Corte de grama, poda de árvores/arbustos, adubação, controle de pragas de jardim, irrigação.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR4PR' => ['name' => 'MANUTENCAO_PREDIAL', 'description' => 'Pintura de áreas comuns (corredores, hall), reparos em alvenaria/pisos comuns, limpeza de fachada/garagem.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR5EQ' => ['name' => 'MANUTENCAO_EQUIPAMENTOS', 'description' => 'Bombas d\'água, portões eletrônicos, interfones, sistema de CFTV (câmeras).', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR6SI' => ['name' => 'MANUTENCAO_SISTEMAS_INCENDIO', 'description' => 'Recarga/revisão de extintores, teste de mangueiras, manutenção de alarmes e detectores.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR7CP' => ['name' => 'CONTROLE_PRAGAS', 'description' => 'Dedetizações periódicas ou emergenciais (baratas, ratos, cupins, etc.).', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR8RO' => ['name' => 'MANUTENÇÃO PREVENTIVA PORTÃO', 'description' => 'Manutenção preventiva ou paliativa do portão da garagem).', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],

        // Serviços Públicos / Contas de Consumo (SP)
        'SP1EL' => ['name' => 'CEMIG', 'description' => 'Conta de luz de corredores, elevador(es), bombas, portões, iluminação externa.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'SP2AG' => ['name' => 'COPASA', 'description' => 'Conta de água/esgoto para limpeza, jardinagem (se não individualizada), consumo da portaria.', 'distributionMethod' => 'FRACTION', 'isRecurring' => true],
        'SP3GA' => ['name' => 'GAS_TOTAL_A_COMPENSAR', 'description' => 'Total a compensar pelas unidades consumidoras', 'distributionMethod' => 'INDIVIDUAL', 'isRecurring' => true],
        'SP4TC' => ['name' => 'INTERNET_DO_CFTV', 'description' => 'Linha telefônica/internet', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],

        // Pessoal / Folha de Pagamento (PF)
        'PF1SE' => ['name' => 'SALARIOS_ENCARGOS', 'description' => 'Taxa mensal do síndico', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],

        // Serviços Terceirizados (ST)
        'ST1LT' => ['name' => 'LIMPEZA_TERCEIRIZADA', 'description' => 'Contrato com empresa de limpeza.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'ST2AJ' => ['name' => 'ASSESSORIA_JURIDICA_CONTABIL', 'description' => 'Honorários de advogados, contadores, auditorias.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],

        // Administrativo e Financeiro (AF)
        'AF1DB' => ['name' => 'DESPESAS_BANCARIAS', 'description' => 'Tarifas de manutenção de conta, taxas de boletos.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'AF2SG' => ['name' => 'SEGUROS_CONDOMINIO', 'description' => 'Seguro obrigatório do condomínio (incêndio, etc.), seguro de responsabilidade civil do síndico.', 'distributionMethod' => 'FRACTION', 'isRecurring' => false],
        'AF3ML' => ['name' => 'MATERIAL_LIMPEZA', 'description' => 'Produtos de limpeza, sacos de lixo', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'AF4IT' => ['name' => 'IMPOSTOS_TAXAS', 'description' => 'IPTU de áreas comuns (se houver), outras taxas municipais/estaduais.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'AF5CC' => ['name' => 'CORREIOS_CARTORIO', 'description' => 'Despesas com envio de correspondência, reconhecimento de firmas, cópias autenticadas.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],

        'OT1DA' => ['name' => 'DESPESAS_ASSEMBLEIA', 'description' => 'Aluguel de espaço (se necessário), cópias de documentos, envio de convocações.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'OT2DD' => ['name' => 'DESPESAS_DIVERSAS', 'description' => 'Gastos menores e eventuais não classificáveis nas outras categorias.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
    ];
    private SymfonyStyle $io;

    public function __construct(
        private readonly ExpenseTypeRepository $repository,
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
        // Si piden "all", iteramos el array
        if ($input->getOption('all')) {
            $this->io->title('Creating all predefined ExpenseTypes…');

            foreach (self::EXPENSE_TYPES as $code => $data) {
                $id   = new ExpenseTypeId((string) Uuid::random()); // o como genere tu Uuid
                $type = ExpenseType::create(
                    $id,
                    new ExpenseTypeCode($code),
                    new ExpenseTypeName($data['name']),
                    new ExpenseTypeDistributionMethod($data['distributionMethod']),
                    new ExpenseTypeDescription($data['description']),
                );
                $this->repository->save($type, true);
                $this->io->writeln(sprintf(
                    ' ✓ [%s] %s (recurring: %s)',
                    $code,
                    $data['name'],
                    $data['isRecurring'] ? 'yes' : 'no',
                ));
            }
            $this->io->success('All expense types have been created.');

            return Command::SUCCESS;
        }

        // ----------------------------------------------------
        // Modo manual / individual (comportamiento actual)
        // ----------------------------------------------------
        $id                 = (string) $input->getArgument('id');
        $code               = (string) $input->getArgument('code');
        $name               = (string) $input->getArgument('name');
        $distributionMethod = (string) $input->getArgument('distributionMethod');
        $description        = $input->getArgument('description') ?: null;
        $force              = (bool) $input->getOption('force');

        if (!in_array($distributionMethod, [ExpenseType::EQUAL, ExpenseType::FRACTION, ExpenseType::INDIVIDUAL], true)) {
            throw InvalidArgumentException::createFromArgument('distributionMethod');
        }

        if (!$force && null !== $this->repository->findOneByIdOrFail($id)) {
            throw new RuntimeException(sprintf(
                'ExpenseType con id "%s" ya existe. Usa --force para sobrescribir.',
                $id,
            ));
        }

        $expenseType = ExpenseType::create(
            new ExpenseTypeId($id),
            new ExpenseTypeCode($code),
            new ExpenseTypeName($name),
            new ExpenseTypeDistributionMethod($distributionMethod),
            new ExpenseTypeDescription($description),
        );

        $this->repository->save($expenseType, true);

        $this->io->success(sprintf(
            'ExpenseType "%s" (code: %s) creado con éxito.',
            $name,
            $code,
        ));

        return Command::SUCCESS;
    }

    private function notEmpty(string $arg, string $value): string
    {
        if ('' === trim($value)) {
            throw InvalidArgumentException::createFromArgument($arg);
        }

        return $value;
    }
}
