<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Expense\Domain\ExpenseType;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

use function json_encode;

final class ClosePeriodAndGenerationGuardControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createAuthenticatedClient();
    }

    public function testItRejectsSlipGenerationWhenPeriodIsAlreadyClosed(): void
    {
        // Step 1: Create resident units
        $unit = ResidentUnitMother::create(unit: new \App\Context\ResidentUnit\Domain\ResidentUnitVO('101'));
        $this->entityManager->persist($unit);

        // Step 2: Seed catalogs and create expense type
        $container = static::getContainer();
        $seeder = $container->get(\App\Context\Setup\Application\Service\CatalogSeeder::class);
        $seeder->ensureSeeded();

        $expenseType = ExpenseTypeMother::create();
        $this->entityManager->persist($expenseType);

        // Step 3: Create an expense for January 2025
        $expense = ExpenseMother::create(
            amount: 10000,
            type: $expenseType,
            dueDate: new \DateTime('2025-01-15'),
            description: 'Test expense',
        );
        $this->entityManager->persist($expense->account());
        $this->entityManager->persist($expense->type());
        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        // Step 4: Generate slips for January 2025
        $generatePayload = [
            'targetMonth' => '2025-01',
            'force' => false,
        ];

        $this->client->request(
            'POST',
            '/api/v1/slips/generation',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($generatePayload),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Step 5: Close the period for January 2025
        $this->client->request(
            'POST',
            '/api/v1/periods/2025-01/close',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Step 6: Try to generate slips again for the closed period
        $this->client->request(
            'POST',
            '/api/v1/slips/generation',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($generatePayload),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }
}
