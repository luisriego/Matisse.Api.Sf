<?php

declare(strict_types=1);

namespace App\Tests\Context\BankStatement\Infrastructure\Http\Controller;

use App\Context\Account\Domain\AccountName;
use App\Context\Account\Domain\AccountRepository;
use App\Context\BankStatement\Application\Service\SettlementAccountResolver;
use App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines\ConfirmBankOfxLinesCommandHandler;
use App\Context\Income\Domain\IncomeRepository;
use App\Context\Income\Domain\ValueObject\IncomeTypeName;
use App\Context\Setup\Application\Service\SetupStatusChecker;
use App\Context\ResidentUnit\Domain\ResidentUnitIdealFraction;
use App\Context\ResidentUnit\Domain\ResidentUnitVO;
use App\Context\Slip\Domain\Slip;
use App\Context\Slip\Domain\ValueObject\SlipAmount;
use App\Context\Slip\Domain\ValueObject\SlipDueDate;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Context\Income\Domain\IncomeTypeMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Context\Slip\Domain\SlipMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

final class OfxConfirmPostControllerTest extends ApiTestCase
{
    private const BANK_ACCOUNT_ID = '3033132774';
    private const LINE_KEY_A        = 'FIT-TEST-001';
    private const LINE_KEY_B        = 'FIT-TEST-002';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_imports_confirmed_lines_and_returns_created(): void
    {
        $account     = AccountMother::create();
        $expenseType = ExpenseTypeMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->persist($expenseType);
        $this->entityManager->flush();

        $payload = $this->buildPayload($expenseType->id(), $account->id(), [self::LINE_KEY_A]);

        $this->client->request(
            'POST',
            '/api/v1/bank/ofx-confirm',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, $data['imported']);
        $this->assertNull($data['consolidatedIncomeId']);
        $this->assertNull($data['settlementMonth']);
    }

    public function test_it_accepts_legacy_json_property_alias_for_import_line_key(): void
    {
        $account     = AccountMother::create();
        $expenseType = ExpenseTypeMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->persist($expenseType);
        $this->entityManager->flush();

        $payload = [
            'bankAccountId' => self::BANK_ACCOUNT_ID,
            'lines'         => [
                [
                    'fitId'         => 'LEGACY-ALIAS-001',
                    'amountInCents' => 15000,
                    'postedAt'      => '2026-03-15',
                    'memo'          => 'Legacy key test',
                    'expenseTypeId' => $expenseType->id(),
                    'accountId'     => $account->id(),
                    'dueDate'       => '2026-03-15',
                ],
            ],
        ];

        $this->client->request(
            'POST',
            '/api/v1/bank/ofx-confirm',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, $data['imported']);
    }

    public function test_it_consolidates_settlement_credits_and_marks_paid_on_latest_posted_date(): void
    {
        $account    = AccountMother::create();
        $incomeType = IncomeTypeMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->persist($incomeType);

        // SlipGeneration(expenseMonth=February) → slips with dueDate in March (N+1 rule).
        // Boletos posted in March → validation queries sumAmountByDueDateMonth(March) = 100000.
        $this->createSlip(30000, '2026-03-10');
        $this->createSlip(70000, '2026-03-10');
        $this->entityManager->flush();

        $payload = [
            'bankAccountId' => self::BANK_ACCOUNT_ID,
            'lines'         => [
                [
                    'importLineKey' => 'FIT-CR-001',
                    'amountInCents' => 30000,
                    'postedAt'      => '2026-03-04',
                    'memo'          => 'BOLETOS RECEBIDOS',
                    'lineType'      => 'income',
                    'creditKind'    => 'boleto_settlement',
                    'incomeTypeId'  => $incomeType->id(),
                    'accountId'     => $account->id(),
                    'dueDate'       => '2026-03-04',
                ],
                [
                    'importLineKey' => 'FIT-CR-002',
                    'amountInCents' => 70000,
                    'postedAt'      => '2026-03-09',
                    'memo'          => 'BOLETOS RECEBIDOS',
                    'lineType'      => 'income',
                    'creditKind'    => 'boleto_settlement',
                    'incomeTypeId'  => $incomeType->id(),
                    'accountId'     => $account->id(),
                    'dueDate'       => '2026-03-09',
                ],
            ],
        ];

        $this->client->request(
            'POST',
            '/api/v1/bank/ofx-confirm',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(2, $data['imported']);
        $this->assertNotNull($data['consolidatedIncomeId']);
        $this->assertSame('2026-02', $data['settlementMonth']);

        $incomeRepository = self::getContainer()->get(IncomeRepository::class);
        $incomes          = $incomeRepository->findAll();
        $this->assertCount(1, $incomes, 'Both CREDIT lines must collapse into a single consolidated income.');

        $income = $incomes[0];
        $this->assertSame(100000, $income->amount(), 'Consolidated amount must be the sum of credits.');
        $this->assertNull($income->residentUnit(), 'Settlement income is lump-sum, no resident unit.');
        $this->assertSame($incomeType->id(), $income->incomeType()?->id());
        $this->assertNotNull($income->paidAt(), 'Settlement income must be created already paid.');
        $this->assertSame('2026-03-09', $income->paidAt()->format('Y-m-d'), 'paidAt = max(postedAt).');
        $this->assertSame('Compensação de boletos — 02/2026', $income->description());

        $this->assertSame(100000, $data['settlementExpectedSlipTotalCents']);
        $this->assertTrue($data['settlementValidatedAgainstSlips']);
    }

    public function test_it_accepts_settlement_when_slip_total_is_zero_greenfield(): void
    {
        $account    = AccountMother::create();
        $incomeType = IncomeTypeMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->persist($incomeType);
        $this->entityManager->flush();

        $payload = [
            'bankAccountId' => self::BANK_ACCOUNT_ID,
            'lines'         => [
                [
                    'importLineKey' => 'FIT-CR-GREEN',
                    'amountInCents' => 636116,
                    'postedAt'      => '2026-03-09',
                    'memo'          => 'BOLETOS RECEBIDOS',
                    'lineType'      => 'income',
                    'creditKind'    => 'boleto_settlement',
                    'incomeTypeId'  => $incomeType->id(),
                    'accountId'     => $account->id(),
                    'dueDate'       => '2026-03-09',
                ],
            ],
        ];

        $this->client->request(
            'POST',
            '/api/v1/bank/ofx-confirm',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, $data['imported']);
        $this->assertNotNull($data['consolidatedIncomeId']);
        $this->assertSame('2026-02', $data['settlementMonth']);
        $this->assertSame(0, $data['settlementExpectedSlipTotalCents']);
        $this->assertFalse($data['settlementValidatedAgainstSlips']);

        $incomeRepository = self::getContainer()->get(IncomeRepository::class);
        $incomes          = $incomeRepository->findAll();
        $this->assertCount(1, $incomes);
        $this->assertSame(636116, $incomes[0]->amount());
    }

    public function test_it_returns_422_when_settlement_does_not_match_expected_total(): void
    {
        $account    = AccountMother::create();
        $incomeType = IncomeTypeMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->persist($incomeType);
        $this->createSlip(100000, '2026-03-10'); // expected = 100000 — dueDate March = same month as posting
        $this->entityManager->flush();

        $payload = [
            'bankAccountId' => self::BANK_ACCOUNT_ID,
            'lines'         => [
                [
                    'importLineKey' => 'FIT-CR-SHORT',
                    'amountInCents' => 70000, // received 70000
                    'postedAt'      => '2026-03-09',
                    'memo'          => 'BOLETOS RECEBIDOS',
                    'lineType'      => 'income',
                    'creditKind'    => 'boleto_settlement',
                    'incomeTypeId'  => $incomeType->id(),
                    'accountId'     => $account->id(),
                    'dueDate'       => '2026-03-09',
                ],
            ],
        ];

        $this->client->request(
            'POST',
            '/api/v1/bank/ofx-confirm',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('boleto_settlement_mismatch', $data['error']);
        $this->assertSame('2026-02', $data['settlementMonth']);
        $this->assertSame(100000, $data['expectedCents']);
        $this->assertSame(70000,  $data['receivedCents']);
        $this->assertSame(-30000, $data['diffCents']);

        $incomeRepository = self::getContainer()->get(IncomeRepository::class);
        $this->assertCount(0, $incomeRepository->findAll(), 'Mismatch must not persist any income.');
    }

    public function test_it_validates_and_consolidates_real_april_2026_statement_example(): void
    {
        $account    = AccountMother::create();
        $incomeType = IncomeTypeMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->persist($incomeType);

        // Real example provided by the user:
        // PREVISÃO ABRIL/2026 total = 8.324,28
        // principal 5.747,63 + syndic 600,00 + extra 1.250,00 + reserve 468,50 + gas 258,15
        // Slips generated from expenseMonth=March have dueDate in April (N+1).
        $this->createSlip(162758, '2026-04-06'); // apt 101
        $this->createSlip(168046, '2026-04-08'); // apt 201
        $this->createSlip(501624, '2026-04-09'); // apt 301+401+501
        $this->entityManager->flush();

        $payload = [
            'bankAccountId' => self::BANK_ACCOUNT_ID,
            'lines'         => [
                [
                    'importLineKey' => 'FIT-APR-001',
                    'amountInCents' => 162758,
                    'postedAt'      => '2026-04-06',
                    'memo'          => 'BOLETOS RECEBIDOS 06/04S',
                    'lineType'      => 'income',
                    'creditKind'    => 'boleto_settlement',
                    'incomeTypeId'  => $incomeType->id(),
                    'accountId'     => $account->id(),
                    'dueDate'       => '2026-04-06',
                ],
                [
                    'importLineKey' => 'FIT-APR-002',
                    'amountInCents' => 168046,
                    'postedAt'      => '2026-04-08',
                    'memo'          => 'BOLETOS RECEBIDOS 08/04S',
                    'lineType'      => 'income',
                    'creditKind'    => 'boleto_settlement',
                    'incomeTypeId'  => $incomeType->id(),
                    'accountId'     => $account->id(),
                    'dueDate'       => '2026-04-08',
                ],
                [
                    'importLineKey' => 'FIT-APR-003',
                    'amountInCents' => 501624,
                    'postedAt'      => '2026-04-09',
                    'memo'          => 'BOLETOS RECEBIDOS 09/04S',
                    'lineType'      => 'income',
                    'creditKind'    => 'boleto_settlement',
                    'incomeTypeId'  => $incomeType->id(),
                    'accountId'     => $account->id(),
                    'dueDate'       => '2026-04-09',
                ],
            ],
        ];

        $this->client->request(
            'POST',
            '/api/v1/bank/ofx-confirm',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(3, $data['imported']);
        $this->assertSame(832428, $data['settlementExpectedSlipTotalCents']);
        $this->assertTrue($data['settlementValidatedAgainstSlips']);

        // Settlement month in response means "expense month" (previous to posting month).
        $this->assertSame('2026-03', $data['settlementMonth']);

        $incomeRepository = self::getContainer()->get(IncomeRepository::class);
        $incomes          = $incomeRepository->findAll();
        $this->assertCount(1, $incomes);

        $income = $incomes[0];
        $this->assertSame(832428, $income->amount());
        $this->assertSame('2026-04-09', $income->paidAt()?->format('Y-m-d'));
        $this->assertSame('Compensação de boletos — 03/2026', $income->description());
    }

    public function test_it_splits_real_april_2026_settlement_into_target_accounts(): void
    {
        // Accounts with names matching SettlementAccountResolver patterns.
        $accountPrincipal = AccountMother::create(name: new AccountName('Conta Principal'));
        $accountPrincipal->enable();
        $accountExtra = AccountMother::create(name: new AccountName('Fundo de Obra'));
        $accountExtra->enable();
        $accountReserve = AccountMother::create(name: new AccountName('Fundo de Reserva'));
        $accountReserve->enable();
        $accountGas = AccountMother::create(name: new AccountName('Conta Gás'));
        $accountGas->enable();
        $this->entityManager->persist($accountPrincipal);
        $this->entityManager->persist($accountExtra);
        $this->entityManager->persist($accountReserve);
        $this->entityManager->persist($accountGas);

        // Income types with names matching SettlementAccountResolver patterns.
        $incomeTypeCondominial = IncomeTypeMother::create(name: new IncomeTypeName('Taxa Condominial'));
        $incomeTypeCotaExtra   = IncomeTypeMother::create(name: new IncomeTypeName('Cota Extra'));
        $this->entityManager->persist($incomeTypeCondominial);
        $this->entityManager->persist($incomeTypeCotaExtra);

        // Five active units (same shape as condominium example).
        $residentUnits = [
            ResidentUnitMother::create(unit: new ResidentUnitVO('101'), idealFraction: new ResidentUnitIdealFraction(0.18131761)),
            ResidentUnitMother::create(unit: new ResidentUnitVO('201'), idealFraction: new ResidentUnitIdealFraction(0.18131761)),
            ResidentUnitMother::create(unit: new ResidentUnitVO('301'), idealFraction: new ResidentUnitIdealFraction(0.18131761)),
            ResidentUnitMother::create(unit: new ResidentUnitVO('401'), idealFraction: new ResidentUnitIdealFraction(0.19816931)),
            ResidentUnitMother::create(unit: new ResidentUnitVO('501'), idealFraction: new ResidentUnitIdealFraction(0.25787791)),
        ];
        foreach ($residentUnits as $unit) {
            $this->entityManager->persist($unit);
        }

        $this->entityManager->flush();

        // Wire SettlementAccountResolver with enabled=true resolving from DB by name.
        $resolver = new SettlementAccountResolver(
            self::getContainer()->get(AccountRepository::class),
            self::getContainer()->get(\App\Context\Income\Domain\IncomeTypeRepository::class),
            true,
        );
        $this->assertTrue($resolver->shouldSplit());
        self::getContainer()->set(SettlementAccountResolver::class, $resolver);
        self::getContainer()->set(
            ConfirmBankOfxLinesCommandHandler::class,
            new ConfirmBankOfxLinesCommandHandler(
                self::getContainer()->get(\App\Context\Slip\Domain\SlipRepository::class),
                self::getContainer()->get(\Symfony\Component\Messenger\MessageBusInterface::class),
                self::getContainer()->get(\App\Context\Slip\Domain\SlipGenerationParameterSnapshotRepository::class),
                self::getContainer()->get(\App\Context\Slip\Domain\Service\SlipGenerationBreakdownBuilder::class),
                self::getContainer()->get(\App\Context\Expense\Domain\ExpenseRepository::class),
                self::getContainer()->get(\App\Context\Expense\Domain\RecurringExpenseRepository::class),
                self::getContainer()->get(\App\Context\ResidentUnit\Domain\ResidentUnitRepository::class),
                $resolver,
                self::getContainer()->get(SetupStatusChecker::class),
                self::getContainer()->get(AccountRepository::class),
                null,
                null,
            ),
        );
        // Gas configuration so gas component equals 258,15 BRL for expenseMonth=2026-03
        // (resolver uses month 2026-02 and previous 2026-01).
        self::getContainer()->get(\Symfony\Component\Messenger\MessageBusInterface::class)
            ->dispatch(new \App\Context\Gas\Application\UseCase\SetGasPrice\SetGasPriceCommand(2600));

        $janReadings = [100.000, 200.000, 300.000, 400.000, 500.000];
        $febReadings = [101.091, 203.125, 300.441, 402.405, 502.867];
        foreach ($residentUnits as $idx => $unit) {
            self::getContainer()->get(\Symfony\Component\Messenger\MessageBusInterface::class)
                ->dispatch(new \App\Context\Gas\Application\UseCase\RecordGasReading\RecordGasReadingCommand(
                    UuidMother::create(),
                    $unit->id(),
                    2026,
                    1,
                    $janReadings[$idx],
                ));

            self::getContainer()->get(\Symfony\Component\Messenger\MessageBusInterface::class)
                ->dispatch(new \App\Context\Gas\Application\UseCase\RecordGasReading\RecordGasReadingCommand(
                    UuidMother::create(),
                    $unit->id(),
                    2026,
                    2,
                    $febReadings[$idx],
                ));
        }

        // Slips due in April (expected sum for settlement validation).
        $this->createSlip(162758, '2026-04-06');
        $this->createSlip(168046, '2026-04-08');
        $this->createSlip(501624, '2026-04-09');
        $this->entityManager->flush();

        $handler = self::getContainer()->get(ConfirmBankOfxLinesCommandHandler::class);
        $result = $handler(new \App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines\ConfirmBankOfxLinesCommand(
            bankAccountId: self::BANK_ACCOUNT_ID,
            lines: [
                new \App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines\ConfirmLineDto(
                    importLineKey: 'FIT-APR-SPLIT-001',
                    amountInCents: 162758,
                    postedAt: '2026-04-06',
                    memo: 'BOLETOS RECEBIDOS 06/04S',
                    accountId: $accountPrincipal->id(),
                    dueDate: '2026-04-06',
                    lineType: 'income',
                    incomeTypeId: $incomeTypeCondominial->id(),
                    creditKind: 'boleto_settlement',
                ),
                new \App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines\ConfirmLineDto(
                    importLineKey: 'FIT-APR-SPLIT-002',
                    amountInCents: 168046,
                    postedAt: '2026-04-08',
                    memo: 'BOLETOS RECEBIDOS 08/04S',
                    accountId: $accountPrincipal->id(),
                    dueDate: '2026-04-08',
                    lineType: 'income',
                    incomeTypeId: $incomeTypeCondominial->id(),
                    creditKind: 'boleto_settlement',
                ),
                new \App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines\ConfirmLineDto(
                    importLineKey: 'FIT-APR-SPLIT-003',
                    amountInCents: 501624,
                    postedAt: '2026-04-09',
                    memo: 'BOLETOS RECEBIDOS 09/04S',
                    accountId: $accountPrincipal->id(),
                    dueDate: '2026-04-09',
                    lineType: 'income',
                    incomeTypeId: $incomeTypeCondominial->id(),
                    creditKind: 'boleto_settlement',
                ),
            ],
            settlementExtraFeePerUnitCents: 25000,
            settlementReserveFundPerUnitCents: 9370,
        ));

        $this->assertSame(3, $result->imported);
        $this->assertSame(832428, $result->settlementExpectedSlipTotalCents);
        $this->assertTrue($result->settlementValidatedAgainstSlips);
        $this->assertSame('2026-03', $result->settlementMonth);

        $splitRows = $result->settlementSplitIncomeIds;
        $this->assertNotEmpty($splitRows);

        $rowsByComponent = [];
        foreach ($splitRows as $row) {
            $rowsByComponent[$row['component']] = $row;
        }

        $this->assertArrayHasKey('base', $rowsByComponent);
        $this->assertArrayHasKey('extra', $rowsByComponent);
        $this->assertArrayHasKey('reserve', $rowsByComponent);
        $this->assertArrayHasKey('gas', $rowsByComponent);
        $this->assertGreaterThan(0, $rowsByComponent['base']['amountCents']);
        $this->assertGreaterThan(0, $rowsByComponent['extra']['amountCents']);
        $this->assertGreaterThan(0, $rowsByComponent['reserve']['amountCents']);

        $sumSplit = 0;
        foreach ($splitRows as $row) {
            $sumSplit += $row['amountCents'];
        }
        $this->assertSame(832428, $sumSplit);

        $incomeRepository = self::getContainer()->get(IncomeRepository::class);
        foreach ($splitRows as $row) {
            $income = $incomeRepository->findOneByIdOrFail($row['incomeId']);
            $this->assertSame($row['amountCents'], $income->amount());

            $expectedAccountId = match ($row['component']) {
                'base', 'syndic' => $accountPrincipal->id(),
                'extra'          => $accountExtra->id(),
                'reserve'        => $accountReserve->id(),
                'gas'            => $accountGas->id(),
                default          => null,
            };
            $expectedIncomeTypeId = match ($row['component']) {
                'base', 'syndic', 'reserve', 'gas' => $incomeTypeCondominial->id(),
                'extra'                             => $incomeTypeCotaExtra->id(),
                default                             => null,
            };

            $this->assertSame($expectedAccountId, $income->accountId());
            $this->assertSame($expectedIncomeTypeId, $income->incomeType()?->id());
        }
    }

    public function test_other_credits_do_not_trigger_settlement_validation_and_produce_individual_incomes(): void
    {
        $account    = AccountMother::create();
        $incomeType = IncomeTypeMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->persist($incomeType);
        $this->entityManager->flush();
        // Note: no Slips persisted → expected total for Feb 2026 is 0.

        $payload = [
            'bankAccountId' => self::BANK_ACCOUNT_ID,
            'lines'         => [
                [
                    'importLineKey' => 'FIT-OTHER-001',
                    'amountInCents' => 1250,
                    'postedAt'      => '2026-03-31',
                    'memo'          => 'RENDIMENTO POUPANCA',
                    'lineType'      => 'income',
                    'creditKind'    => 'other',
                    'incomeTypeId'  => $incomeType->id(),
                    'accountId'     => $account->id(),
                    'dueDate'       => '2026-03-31',
                    'description'   => 'Bank interest',
                ],
            ],
        ];

        $this->client->request(
            'POST',
            '/api/v1/bank/ofx-confirm',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, $data['imported']);
        $this->assertNull($data['consolidatedIncomeId']);
        $this->assertNull($data['settlementMonth']);

        $incomeRepository = self::getContainer()->get(IncomeRepository::class);
        $incomes          = $incomeRepository->findAll();
        $this->assertCount(1, $incomes);
        $this->assertSame(1250, $incomes[0]->amount());
        $this->assertSame('2026-03-31', $incomes[0]->paidAt()?->format('Y-m-d'));
    }

    public function test_repeated_settlement_confirm_creates_another_consolidated_income(): void
    {
        $account    = AccountMother::create();
        $incomeType = IncomeTypeMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->persist($incomeType);
        $this->createSlip(50000, '2026-03-10'); // dueDate March = same month as posting
        $this->entityManager->flush();

        $payload = [
            'bankAccountId' => self::BANK_ACCOUNT_ID,
            'lines'         => [
                [
                    'importLineKey' => 'FIT-CR-IDEMP',
                    'amountInCents' => 50000,
                    'postedAt'      => '2026-03-09',
                    'memo'          => 'BOLETOS RECEBIDOS',
                    'lineType'      => 'income',
                    'creditKind'    => 'boleto_settlement',
                    'incomeTypeId'  => $incomeType->id(),
                    'accountId'     => $account->id(),
                    'dueDate'       => '2026-03-09',
                ],
            ],
        ];

        $this->client->request('POST', '/api/v1/bank/ofx-confirm', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->client->request('POST', '/api/v1/bank/ofx-confirm', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, $data['imported']);
        $this->assertNotNull($data['consolidatedIncomeId']);

        $incomeRepository = self::getContainer()->get(IncomeRepository::class);
        $this->assertCount(2, $incomeRepository->findAll());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /** @param string[] $lineKeys */
    private function buildPayload(string $expenseTypeId, string $accountId, array $lineKeys): array
    {
        return [
            'bankAccountId' => self::BANK_ACCOUNT_ID,
            'lines'         => array_map(
                static fn (string $lineKey) => [
                    'importLineKey' => $lineKey,
                    'amountInCents' => 15000,
                    'postedAt'      => '2026-03-15',
                    'memo'          => 'TEST TRANSACTION ' . $lineKey,
                    'expenseTypeId' => $expenseTypeId,
                    'accountId'     => $accountId,
                    'dueDate'       => '2026-03-15',
                    'description'   => 'Test import ' . $lineKey,
                ],
                $lineKeys,
            ),
        ];
    }

    private function createSlip(int $amountCents, string $dueDate): Slip
    {
        $residentUnit = ResidentUnitMother::create();
        $this->entityManager->persist($residentUnit);

        $slip = SlipMother::create(
            amount:       new SlipAmount($amountCents),
            residentUnit: $residentUnit,
            dueDate:      new SlipDueDate(new DateTime($dueDate)),
        );
        $this->entityManager->persist($slip);

        return $slip;
    }
}
