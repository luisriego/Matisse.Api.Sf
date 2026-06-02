<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Domain\RecurringExpense;
use App\Tests\Context\Expense\Domain\RecurringExpenseMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class RecurringExpenseRemoveDeleteControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function testItShouldRemoveRecurringExpense(): void
    {
        // 1. Create an initial recurring expense to be removed
        $recurringExpense = RecurringExpenseMother::create();
        $recurringExpenseId = $recurringExpense->id();

        $this->entityManager->persist($recurringExpense->type());
        $this->entityManager->persist($recurringExpense);
        $this->entityManager->flush();

        // 2. Send the DELETE request
        $this->client->request(
            'DELETE',
            '/api/v1/recurring-expenses/' . $recurringExpenseId,
        );

        // 3. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // 4. Assert that the recurring expense was removed from the database
        $this->entityManager->clear();
        $removedExpense = $this->entityManager->find(RecurringExpense::class, $recurringExpenseId);

        $this->assertNull($removedExpense);
    }
}
