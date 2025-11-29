<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Domain\RecurringExpense;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class RecurringExpenseCreatePutControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_should_create_recurring_expense(): void
    {
        // 1. Create dependencies
        $account = AccountMother::create();
        $this->entityManager->persist($account);
        $type = ExpenseTypeMother::create();
        $this->entityManager->persist($type);
        $this->entityManager->flush();

        // 2. Define the payload
        $recurringExpenseId = UuidMother::create();
        $payload = [
            'id' => $recurringExpenseId,
            'amount' => 15000,
            'type' => $type->id(),
            'accountId' => $account->id(),
            'dueDay' => 15,
            'monthsOfYear' => [1, 6, 12],
            'startDate' => '2025-01-01',
            'endDate' => '2025-12-31',
            'description' => 'Annual Subscription',
            'notes' => 'Test notes',
            'hasPredefinedAmount' => true,
        ];

        // 3. Send the PUT request
        $this->client->request(
            'PUT',
            '/api/v1/recurring-expenses/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // 4. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // 5. Assert that the recurring expense was created in the database
        $this->entityManager->clear();
        /** @var RecurringExpense|null $createdExpense */
        $createdExpense = $this->entityManager->find(RecurringExpense::class, $recurringExpenseId);

        $this->assertNotNull($createdExpense);
        $this->assertEquals($payload['description'], $createdExpense->description());
        $this->assertEquals($payload['amount'], $createdExpense->amount());
    }
}
