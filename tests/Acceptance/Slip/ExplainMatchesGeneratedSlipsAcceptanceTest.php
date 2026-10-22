<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Slip;

use App\Context\Expense\Domain\ExpenseTypeRepository;
use App\Context\Setup\Application\Service\CatalogSeeder;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;
use function sprintf;

final class ExplainMatchesGeneratedSlipsAcceptanceTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();

        // Seed reference catalogs (expense/income types) so the system can resolve codes like SP3GA
        $container = static::getContainer();
        $seeder = $container->get(CatalogSeeder::class);
        $seeder->ensureSeeded();
    }

    public function testExplainAmountsMatchGeneratedSlipsSum(): void
    {
        // 1. Create resident units with known ideal fractions
        $unitA = ResidentUnitMother::create(
            null,
            null,
            new \App\Context\ResidentUnit\Domain\ResidentUnitIdealFraction(0.60),
        );
        $unitB = ResidentUnitMother::create(
            null,
            null,
            new \App\Context\ResidentUnit\Domain\ResidentUnitIdealFraction(0.40),
        );
        $this->entityManager->persist($unitA);
        $this->entityManager->persist($unitB);

        // 2. Create an EQUAL-type expense for the target month
        $expenseType = ExpenseTypeMother::create();
        $targetMonth = '2024-07';
        $dueDate = new \DateTime('2024-07-15');

        $expense = ExpenseMother::create(
            amount: 10000, // 100.00 in cents
            type: $expenseType,
            dueDate: $dueDate,
            description: 'Test EQUAL expense',
        );
        $this->entityManager->persist($expense->account());
        $this->entityManager->persist($expense->type());
        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        // 3. Call explain endpoint
        $this->client->request('GET', '/api/v1/slips/generation/explain', [
            'targetMonth' => $targetMonth,
            'extraFee' => 0,
            'reserveFund' => 0,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $explainData = json_decode($this->client->getResponse()->getContent(), true);

        // 4. Generate slips for the same month
        $this->client->request('POST', '/api/v1/slips/generation', [
            'targetMonth' => $targetMonth,
            'force' => true,
            'extraFee' => 0,
            'reserveFund' => 0,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $generateData = json_decode($this->client->getResponse()->getContent(), true);

        // 5. Retrieve generated slips by due month (month + 1)
        $dueYear = 2024;
        $dueMonth = 8;
        $dueMonthParam = sprintf('%04d-%02d', $dueYear, $dueMonth);

        $this->client->request('GET', '/api/v1/slips/by-month', ['targetMonth' => $dueMonthParam]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $slipsData = json_decode($this->client->getResponse()->getContent(), true);

        // 6. Assert that the explain total matches the sum of generated slip amounts
        $explainTotalCents = (int) $explainData['totals']['totalCents'];
        $generatedSlipsAmountSum = 0;

        foreach ($generateData['slips'] as $slip) {
            $generatedSlipsAmountSum += (int) $slip['amount'];
        }

        // Allow ±1 cent tolerance for rounding differences
        $this->assertEquals(
            $explainTotalCents,
            $generatedSlipsAmountSum,
            sprintf(
                'Explain total (%d cents) must match sum of generated slips (%d cents).',
                $explainTotalCents,
                $generatedSlipsAmountSum,
            ),
        );

        // 7. Assert per-unit amounts match
        $explainUnitsById = [];
        foreach ($explainData['units'] as $unit) {
            $explainUnitsById[$unit['residentUnitId']] = (int) $unit['totalCents'];
        }

        foreach ($generateData['slips'] as $slip) {
            $unitId = $slip['residentUnit']['id'];
            $this->assertArrayHasKey(
                $unitId,
                $explainUnitsById,
                sprintf('Unit %s should exist in explain response.', $unitId),
            );

            $this->assertEquals(
                $explainUnitsById[$unitId],
                (int) $slip['amount'],
                sprintf(
                    'Unit %s: explain amount (%d) must match generated slip amount (%d).',
                    $unitId,
                    $explainUnitsById[$unitId],
                    (int) $slip['amount'],
                ),
            );
        }
    }
}
