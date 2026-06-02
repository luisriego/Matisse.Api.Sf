<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Domain\Expense;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\Response;

use function json_encode;

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
    public function testItShouldCompensateExpense(): void
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
            json_encode($payload),
        );

        // 4. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // 5. Assert the changes in the database
        $this->entityManager->clear();

        // Assert that the original expense remains and only its amount changes
        $updatedExpense = $this->entityManager->find(Expense::class, $originalExpenseId);
        $this->assertNotNull($updatedExpense);
        $this->assertEquals($compensatedAmount, $updatedExpense->amount());
    }
}
