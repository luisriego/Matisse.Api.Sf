<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Domain\ValueObject\ExpenseEndDate;
use App\Context\Expense\Domain\ValueObject\ExpenseStartDate;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Context\Expense\Domain\RecurringExpenseMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;

final class GetRecurringExpensesByYearControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function testItShouldReturnRecurringExpensesForAGivenYear(): void
    {
        // 1. Create recurring expenses for the test scenario
        $year = 2025;

        // Expense that should be found
        $account1 = AccountMother::create();
        $type1 = ExpenseTypeMother::create(id: UuidMother::create());
        $startDate2025 = new ExpenseStartDate(new DateTime("{$year}-01-01"));
        $endDate2025 = new ExpenseEndDate(new DateTime("{$year}-12-31"));
        $expenseIn2025 = RecurringExpenseMother::create(
            accountId: $account1->id(),
            expenseType: $type1,
            startDate: $startDate2025,
            endDate: $endDate2025,
        );

        // Expense that should NOT be found
        $account2 = AccountMother::create();
        $type2 = ExpenseTypeMother::create(id: UuidMother::create());
        $startDate2026 = new ExpenseStartDate(new DateTime('2026-01-01'));
        $endDate2026 = new ExpenseEndDate(new DateTime('2026-12-31'));
        $expenseIn2026 = RecurringExpenseMother::create(
            accountId: $account2->id(),
            expenseType: $type2,
            startDate: $startDate2026,
            endDate: $endDate2026,
        );

        // 2. Persist all entities
        $this->entityManager->persist($account1);
        $this->entityManager->persist($type1);
        $this->entityManager->persist($expenseIn2025);

        $this->entityManager->persist($account2);
        $this->entityManager->persist($type2);
        $this->entityManager->persist($expenseIn2026);

        $this->entityManager->flush();

        // 3. Send the GET request
        $this->client->request('GET', "/api/v1/recurring-expenses/year/{$year}");

        // 4. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        // 5. Assert the content of the response
        $this->assertIsArray($data);
        $this->assertArrayHasKey('expenses', $data);
        $this->assertCount(1, $data['expenses']);
        $this->assertEquals($expenseIn2025->id(), $data['expenses'][0]['id']);
    }
}
