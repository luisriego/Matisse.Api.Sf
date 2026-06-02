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

final class UpdateExpensePatchControllerTest extends ApiTestCase
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
    public function testItShouldUpdateExpense(): void
    {
        // 1. Create an initial expense to be updated
        $expense = ExpenseMother::create();
        $this->entityManager->persist($expense->account());
        $this->entityManager->persist($expense->type());
        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        // 2. Define the update payload
        $updatedDescription = 'This is the updated description.';
        $updatedDueDate = '2025-02-15';
        $payload = [
            'description' => $updatedDescription,
            'dueDate' => $updatedDueDate,
        ];

        // 3. Send the PATCH request
        $this->client->request(
            'PATCH',
            '/api/v1/expenses/update/' . $expense->id(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        // 4. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // 5. Assert the changes in the database
        $this->entityManager->clear();
        /** @var Expense|null $updatedExpense */
        $updatedExpense = $this->entityManager->find(Expense::class, $expense->id());

        $this->assertNotNull($updatedExpense);
        $this->assertEquals($updatedDescription, $updatedExpense->description());
        $this->assertEquals($updatedDueDate, $updatedExpense->dueDate()->format('Y-m-d'));
    }
}
