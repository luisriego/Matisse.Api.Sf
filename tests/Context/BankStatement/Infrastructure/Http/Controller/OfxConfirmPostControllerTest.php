<?php

declare(strict_types=1);

namespace App\Tests\Context\BankStatement\Infrastructure\Http\Controller;

use App\Context\BankStatement\Domain\BankTransactionImportRepository;
use App\Context\Income\Domain\IncomeRepository;
use App\Context\Slip\Domain\Slip;
use App\Context\Slip\Domain\ValueObject\SlipAmount;
use App\Context\Slip\Domain\ValueObject\SlipDueDate;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Context\Income\Domain\IncomeTypeMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Context\Slip\Domain\SlipMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

final class OfxConfirmPostControllerTest extends ApiTestCase
{
    private const BANK_ACCOUNT_ID = '3033132774';
    private const FIT_ID_A        = 'FIT-TEST-001';
    private const FIT_ID_B        = 'FIT-TEST-002';

    private ?BankTransactionImportRepository $importRepository = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
        $this->importRepository = self::getContainer()->get(BankTransactionImportRepository::class);
    }

    public function test_it_imports_confirmed_lines_and_returns_created(): void
    {
        $account     = AccountMother::create();
        $expenseType = ExpenseTypeMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->persist($expenseType);
        $this->entityManager->flush();

        $payload = $this->buildPayload($expenseType->id(), $account->id(), [self::FIT_ID_A]);

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
        $this->assertSame(0, $data['skipped']);
        $this->assertSame([], $data['skippedFitIds']);
        $this->assertNull($data['consolidatedIncomeId']);
        $this->assertNull($data['settlementMonth']);
    }

    public function test_it_skips_already_imported_fitids_idempotency(): void
    {
        $account     = AccountMother::create();
        $expenseType = ExpenseTypeMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->persist($expenseType);
        $this->entityManager->flush();

        $payload = $this->buildPayload($expenseType->id(), $account->id(), [self::FIT_ID_A, self::FIT_ID_B]);

        $this->client->request('POST', '/api/v1/bank/ofx-confirm', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->client->request('POST', '/api/v1/bank/ofx-confirm', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(0, $data['imported']);
        $this->assertSame(2, $data['skipped']);
        $this->assertContains(self::FIT_ID_A, $data['skippedFitIds']);
        $this->assertContains(self::FIT_ID_B, $data['skippedFitIds']);
    }

    public function test_it_partially_skips_already_imported_fitids(): void
    {
        $account     = AccountMother::create();
        $expenseType = ExpenseTypeMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->persist($expenseType);
        $this->entityManager->flush();

        $payloadA = $this->buildPayload($expenseType->id(), $account->id(), [self::FIT_ID_A]);
        $this->client->request('POST', '/api/v1/bank/ofx-confirm', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payloadA, JSON_THROW_ON_ERROR));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $payloadAB = $this->buildPayload($expenseType->id(), $account->id(), [self::FIT_ID_A, self::FIT_ID_B]);
        $this->client->request('POST', '/api/v1/bank/ofx-confirm', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payloadAB, JSON_THROW_ON_ERROR));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, $data['imported']);
        $this->assertSame(1, $data['skipped']);
        $this->assertSame([self::FIT_ID_A], $data['skippedFitIds']);
    }

    public function test_it_consolidates_settlement_credits_and_marks_paid_on_latest_posted_date(): void
    {
        $account    = AccountMother::create();
        $incomeType = IncomeTypeMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->persist($incomeType);

        // Previous month (February 2026) expected total = 30000 + 70000 = 100000 cents.
        $this->createSlip(30000, '2026-02-10');
        $this->createSlip(70000, '2026-02-10');
        $this->entityManager->flush();

        $payload = [
            'bankAccountId' => self::BANK_ACCOUNT_ID,
            'lines'         => [
                [
                    'fitId'         => 'FIT-CR-001',
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
                    'fitId'         => 'FIT-CR-002',
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
        $this->assertSame(0, $data['skipped']);
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
                    'fitId'         => 'FIT-CR-GREEN',
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
        $this->createSlip(100000, '2026-02-10'); // expected = 100000
        $this->entityManager->flush();

        $payload = [
            'bankAccountId' => self::BANK_ACCOUNT_ID,
            'lines'         => [
                [
                    'fitId'         => 'FIT-CR-SHORT',
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
        $this->assertSame(['FIT-CR-SHORT'], $data['fitIds']);

        $incomeRepository = self::getContainer()->get(IncomeRepository::class);
        $this->assertCount(0, $incomeRepository->findAll(), 'Mismatch must not persist any income.');
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
                    'fitId'         => 'FIT-OTHER-001',
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

    public function test_settlement_idempotency_does_not_duplicate_consolidated_income(): void
    {
        $account    = AccountMother::create();
        $incomeType = IncomeTypeMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->persist($incomeType);
        $this->createSlip(50000, '2026-02-10');
        $this->entityManager->flush();

        $payload = [
            'bankAccountId' => self::BANK_ACCOUNT_ID,
            'lines'         => [
                [
                    'fitId'         => 'FIT-CR-IDEMP',
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
        $this->assertSame(0, $data['imported']);
        $this->assertSame(1, $data['skipped']);
        $this->assertNull($data['consolidatedIncomeId'], 'No new consolidated income on a fully-duplicated settlement.');

        $incomeRepository = self::getContainer()->get(IncomeRepository::class);
        $this->assertCount(1, $incomeRepository->findAll());
    }

    protected function tearDown(): void
    {
        $this->importRepository = null;
        parent::tearDown();
    }

    /** @param string[] $fitIds */
    private function buildPayload(string $expenseTypeId, string $accountId, array $fitIds): array
    {
        return [
            'bankAccountId' => self::BANK_ACCOUNT_ID,
            'lines'         => array_map(
                static fn (string $fitId) => [
                    'fitId'         => $fitId,
                    'amountInCents' => 15000,
                    'postedAt'      => '2026-03-15',
                    'memo'          => 'TEST TRANSACTION ' . $fitId,
                    'expenseTypeId' => $expenseTypeId,
                    'accountId'     => $accountId,
                    'dueDate'       => '2026-03-15',
                    'description'   => 'Test import ' . $fitId,
                ],
                $fitIds,
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
