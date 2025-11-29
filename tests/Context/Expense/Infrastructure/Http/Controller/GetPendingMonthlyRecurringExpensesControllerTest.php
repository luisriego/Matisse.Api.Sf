<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Domain\ValueObject\ExpenseStartDate;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Context\Expense\Domain\RecurringExpenseMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

final class GetPendingMonthlyRecurringExpensesControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_should_return_pending_monthly_recurring_expenses(): void
    {
        // 1. Create a scenario
        $year = 2025;
        $month = 10;
        $account = AccountMother::create();
        $type = ExpenseTypeMother::create(id: UuidMother::create());

        // This recurring expense should be found (it's for October and no individual expense exists yet)
        $pendingRecurring = RecurringExpenseMother::create(
            accountId: $account->id(),
            expenseType: $type,
            monthsOfYear: [$month],
            startDate: new ExpenseStartDate(new DateTime("$year-01-01"))
        );

        // This one should NOT be found because an individual expense for October already exists
        $existingRecurring = RecurringExpenseMother::create(
            accountId: $account->id(),
            expenseType: $type,
            monthsOfYear: [$month],
            startDate: new ExpenseStartDate(new DateTime("$year-01-01"))
        );
        $existingExpense = ExpenseMother::create(
            account: $account,
            type: $type,
            dueDate: new DateTime("$year-$month-15")
        );
        $existingExpense->setRecurringExpense($existingRecurring);

        // This one should NOT be found because it's not for October
        $otherMonthRecurring = RecurringExpenseMother::create(
            accountId: $account->id(),
            expenseType: $type,
            monthsOfYear: [11],
            startDate: new ExpenseStartDate(new DateTime("$year-01-01"))
        );

        // 2. Persist all entities
        $this->entityManager->persist($account);
        $this->entityManager->persist($type);
        $this->entityManager->persist($pendingRecurring);
        $this->entityManager->persist($existingRecurring);
        $this->entityManager->persist($existingExpense);
        $this->entityManager->persist($otherMonthRecurring);
        $this->entityManager->flush();

        // 3. Send the GET request
        $this->client->request('GET', "/api/v1/recurring-expenses/pending-monthly/$month/$year");

        // 4. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        // 5. Assert the content of the response
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals($pendingRecurring->id(), $data[0]['id']);
    }
}
