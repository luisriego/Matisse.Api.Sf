<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Domain\RecurringExpense;
use App\Tests\Context\Expense\Domain\RecurringExpenseMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

use function json_encode;

final class UpdateRecurrentExpensePatchControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function testItShouldUpdateRecurringExpense(): void
    {
        // 1. Create an initial recurring expense
        $recurringExpense = RecurringExpenseMother::create();
        // The Mother creates an Account and ExpenseType, but they are not directly linked as objects.
        // We need to persist the ExpenseType, which IS a direct dependency.
        // The Account is linked by ID, so we don't need to persist it via the RecurringExpense object.
        $this->entityManager->persist($recurringExpense->type());
        $this->entityManager->persist($recurringExpense);
        $this->entityManager->flush();

        // 2. Define the update payload
        $payload = [
            'amount' => 20000,
            'description' => 'Updated Annual Subscription',
            'notes' => 'Updated notes',
            // Add other fields to update as needed
        ];

        // 3. Send the PATCH request
        $this->client->request(
            'PATCH',
            '/api/v1/recurring-expenses/' . $recurringExpense->id(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        // 4. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // 5. Assert that the recurring expense was updated in the database
        $this->entityManager->clear();
        /** @var RecurringExpense|null $updatedExpense */
        $updatedExpense = $this->entityManager->find(RecurringExpense::class, $recurringExpense->id());

        $this->assertNotNull($updatedExpense);
        $this->assertEquals($payload['description'], $updatedExpense->description());
        $this->assertEquals($payload['amount'], $updatedExpense->amount());
        $this->assertEquals($payload['notes'], $updatedExpense->notes());
    }
}
