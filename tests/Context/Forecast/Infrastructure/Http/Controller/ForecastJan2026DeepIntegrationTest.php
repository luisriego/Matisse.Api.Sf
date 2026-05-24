<?php

declare(strict_types=1);

namespace App\Tests\Context\Forecast\Infrastructure\Http\Controller;

use App\Tests\Context\Forecast\Infrastructure\Scenario\CondominiumJan2026Scenario;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Deep integration: reproducible Dec/2025 + Jan/2026 condominium scenario (dev-like data).
 */
final class ForecastJan2026DeepIntegrationTest extends ApiTestCase
{
    private CondominiumJan2026Scenario $scenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createAuthenticatedClient();

        $commandBus = self::getContainer()->get(MessageBusInterface::class);
        $this->scenario = CondominiumJan2026Scenario::seed($this->entityManager, $commandBus);
    }

    public function test_billing_policy_resolve_for_january_and_february(): void
    {
        $this->client->request('GET', '/api/v1/billing-policy/resolve?targetMonth=2026-01');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $jan = json_decode((string) $this->client->getResponse()->getContent(), true)['data'];
        $this->assertSame('2026-01', $jan['sourceMonth']);
        $this->assertTrue($jan['explicit']);
        $this->assertSame(CondominiumJan2026Scenario::BILLING_EXTRA_FEE_PER_UNIT_CENTS, $jan['extraFeePerUnitCents']);

        $this->client->request('GET', '/api/v1/billing-policy/resolve?targetMonth=2026-02');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $feb = json_decode((string) $this->client->getResponse()->getContent(), true)['data'];
        $this->assertSame('2026-01', $feb['sourceMonth']);
        $this->assertFalse($feb['explicit']);
        $this->assertSame(CondominiumJan2026Scenario::BILLING_EXTRA_FEE_PER_UNIT_CENTS, $feb['extraFeePerUnitCents']);
    }

    public function test_explain_january_2026_reflects_reconciled_expenses(): void
    {
        $this->client->request('GET', '/api/v1/slips/generation/explain?targetMonth=2026-01');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('2026-01', $payload['targetMonth']);
        $this->assertCount(CondominiumJan2026Scenario::JAN_EXPENSE_COUNT, $payload['expenseLines']);

        $expenseSum = 0;
        foreach ($payload['expenseLines'] as $line) {
            $expenseSum += (int) $line['amountCents'];
        }
        $this->assertSame(CondominiumJan2026Scenario::JAN_EXPENSE_TOTAL_CENTS, $expenseSum);
        $this->assertSame(5, $payload['payingResidentsCount']);
    }

    public function test_forecast_february_2026_uses_january_gas_and_billing_policy(): void
    {
        $this->client->request(
            'GET',
            '/api/v1/forecast/2026-02?reconciliationMonth=2026-01',
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode((string) $this->client->getResponse()->getContent(), true)['data'];

        $this->assertSame('2026-02', $data['targetMonth']);
        $this->assertSame('2026-01', $data['reconciliationMonth']);
        $this->assertTrue($data['isProjectionOnly']);
        $this->assertSame('previsao', $data['documentKind']);
        $this->assertSame('2026-01', $data['gas']['consumptionCalendarMonth']);
        $this->assertSame('billing_policy', $data['gas']['priceSource']);
        $this->assertSame(CondominiumJan2026Scenario::GAS_PRICE_PER_M3_CENTS, $data['gas']['pricePerM3Cents']);

        $expectedGasByUnit = CondominiumJan2026Scenario::expectedGasByUnitForForecastTargetMonth('2026-02');
        $expectedGasTotal = CondominiumJan2026Scenario::expectedGasTotalForForecastTargetMonth('2026-02');

        $this->assertSame($expectedGasTotal, $data['gas']['totalCents']);
        $this->assertSame(
            CondominiumJan2026Scenario::PREVISAO_FEB_2026_GAS_TOTAL_CENTS,
            $data['totals']['gasTotalCents'],
        );

        $this->assertCount($this->scenario->unitCount(), $data['units']);
        foreach ($data['units'] as $unitRow) {
            $label = $unitRow['unit'];
            $this->assertArrayHasKey($label, $expectedGasByUnit);
            $this->assertSame($expectedGasByUnit[$label], $unitRow['gasCents']);
            $this->assertSame(CondominiumJan2026Scenario::BILLING_EXTRA_FEE_PER_UNIT_CENTS, $unitRow['extraFeePerUnitCents']);
            $this->assertSame(CondominiumJan2026Scenario::BILLING_RESERVE_FUND_PER_UNIT_CENTS, $unitRow['reserveFundPerUnitCents']);
            $this->assertSame(12_000, $unitRow['syndicShareCents'], $label);
            $this->assertSame(0, $unitRow['despesasPrevistasCents'], 'No recurring memory yet → no projected base expenses.');
        }

        $this->assertSame([], $data['expectedExpenseLines']);

        $expectedGrand = CondominiumJan2026Scenario::expectedGrandTotalForForecastTargetMonth('2026-02');
        $this->assertSame($expectedGrand, $data['totals']['boletoGrandTotalCents']);
        $this->assertSame(
            CondominiumJan2026Scenario::PREVISAO_FEB_2026_SYNDIC_TOTAL_CENTS,
            $data['totals']['syndicShareTotalCents'],
        );
        $this->assertSame(
            CondominiumJan2026Scenario::PREVISAO_FEB_2026_EXTRA_TOTAL_CENTS,
            $data['totals']['extraFeeTotalCents'],
        );
        $this->assertSame(
            CondominiumJan2026Scenario::PREVISAO_FEB_2026_RESERVE_TOTAL_CENTS,
            $data['totals']['reserveFundTotalCents'],
        );
    }

    public function test_forecast_february_matches_previsao_excel_golden_per_unit_totals(): void
    {
        $this->client->request(
            'GET',
            '/api/v1/forecast/2026-02?reconciliationMonth=2026-01',
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode((string) $this->client->getResponse()->getContent(), true)['data'];
        $goldenTotals = CondominiumJan2026Scenario::previsaoGoldenUnitTotals('2026-02');
        $goldenSyndic = CondominiumJan2026Scenario::previsaoGoldenUnitSyndicCents('2026-02');

        foreach ($data['units'] as $unitRow) {
            $label = $unitRow['unit'];
            $this->assertArrayHasKey($label, $goldenTotals, sprintf('Missing golden PREVISÃO row for %s.', $label));
            $this->assertSame($goldenSyndic[$label], $unitRow['syndicShareCents'], $label);
            $this->assertSame($goldenTotals[$label], $unitRow['totalCents'], $label);
        }
    }

    public function test_forecast_january_2026_has_zero_gas_without_november_readings(): void
    {
        $this->client->request(
            'GET',
            '/api/v1/forecast/2026-01?reconciliationMonth=2025-12',
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode((string) $this->client->getResponse()->getContent(), true)['data'];

        $this->assertSame('2025-12', $data['gas']['consumptionCalendarMonth']);
        $this->assertSame(0, $data['gas']['totalCents']);
        $this->assertSame([], $data['expectedExpenseLines']);
    }

    public function test_ofx_option_b_then_forecast_includes_expected_expense_line(): void
    {
        $payload = [
            'bankAccountId' => '3033132774',
            'lines' => [
                [
                    'importLineKey' => 'FIT-CEMIG-JAN26-DEEP-INT',
                    'amountInCents' => 27_306,
                    'postedAt' => '2026-01-14',
                    'memo' => 'DA CEMIG 000042299933',
                    'expenseTypeId' => $this->scenario->expenseType->id(),
                    'accountId' => $this->scenario->ledgerAccount->id(),
                    'dueDate' => '2026-02-14',
                    'description' => 'Cemig fevereiro (teste integração)',
                    'isExpectedExpense' => true,
                    'expectedExpense' => [
                        'createOrUpdate' => [
                            'displayName' => 'Cemig',
                            'frequency' => 'monthly',
                            'amountKind' => 'variable',
                            'dueDay' => 14,
                        ],
                    ],
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

        $confirm = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame(1, $confirm['expectedExpensesCreated']);
        $this->assertSame(1, $confirm['expectedExpensesLinked']);

        $this->client->request(
            'GET',
            '/api/v1/forecast/2026-03?reconciliationMonth=2026-02',
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode((string) $this->client->getResponse()->getContent(), true)['data'];
        $lines = $data['expectedExpenseLines'];
        $this->assertCount(1, $lines);
        $this->assertSame('Cemig', $lines[0]['label']);
        $this->assertSame('variable', $lines[0]['amountKind']);
        $this->assertSame(27_306, $lines[0]['amountCents']);
        $this->assertTrue($lines[0]['appliesThisMonth']);
    }
}
