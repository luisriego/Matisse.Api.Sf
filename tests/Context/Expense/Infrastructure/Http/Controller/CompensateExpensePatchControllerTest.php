<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Domain\Expense;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\Response;

final class CompensateExpensePatchControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function test_it_should_compensate_expense(): void
    {
        // 1. Create an initial expense to be compensated
        $originalExpense = ExpenseMother::create();
        $originalExpenseId = $originalExpense->id();

        $this->entityManager->persist($originalExpense->account());
        $this->entityManager->persist($originalExpense->type());
        $this->entityManager->persist($originalExpense);
        $this->entityManager->flush();

        // 2. Define the compensation payload
        $compensatedAmount = 500; // A new, smaller amount
        $payload = ['amount' => $compensatedAmount];

        // 3. Send the PATCH request
        $this->client->request(
            'PATCH',
            '/api/v1/expenses/compensate/' . $originalExpenseId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // 4. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // 5. Assert the changes in the database
        $this->entityManager->clear();

        // Assert that the original expense was removed
        $removedExpense = $this->entityManager->find(Expense::class, $originalExpenseId);
        $this->assertNull($removedExpense);

        // Assert that a new expense was created with the compensated amount
        // We need to query for it, as we don't know its new ID.
        $query = $this->entityManager->createQuery(
            'SELECT e FROM App\Context\Expense\Domain\Expense e WHERE e.amount = :amount'
        )->setParameter('amount', $compensatedAmount);
        
        $newExpenses = $query->getResult();

        $this->assertCount(1, $newExpenses);
        $this->assertEquals($compensatedAmount, $newExpenses[0]->amount());
    }
}
