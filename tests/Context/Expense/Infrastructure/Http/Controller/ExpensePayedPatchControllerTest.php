<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Domain\Expense;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ExpensePayedPatchControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_should_mark_expense_as_paid(): void
    {
        // 1. Create an initial expense that is not paid
        $expense = ExpenseMother::create();
        $this->assertNull($expense->paidAt());

        $this->entityManager->persist($expense->account());
        $this->entityManager->persist($expense->type());
        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        // 2. Send the PATCH request
        $this->client->request(
            'PATCH',
            '/api/v1/expenses/payed/' . $expense->id()
        );

        // 3. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // 4. Assert the changes in the database
        $this->entityManager->clear();
        /** @var Expense|null $paidExpense */
        $paidExpense = $this->entityManager->find(Expense::class, $expense->id());

        $this->assertNotNull($paidExpense);
        $this->assertNotNull($paidExpense->paidAt());
    }
}
