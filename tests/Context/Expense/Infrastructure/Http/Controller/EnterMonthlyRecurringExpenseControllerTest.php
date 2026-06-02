<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Infrastructure\Http\Controller\EnterMonthlyRecurringExpenseController;
use App\Shared\Domain\Exception\InvalidDataException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Context\Expense\Domain\RecurringExpenseMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

use function json_encode;

/**
 * @covers \App\Context\Expense\Infrastructure\Http\Controller\EnterMonthlyRecurringExpenseController
 */
final class EnterMonthlyRecurringExpenseControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function testItShouldEnterMonthlyRecurringExpense(): void
    {
        // 1. Create a recurring expense to be used as a template
        $account = AccountMother::create();
        $type = ExpenseTypeMother::create(id: UuidMother::create());
        $recurringExpense = RecurringExpenseMother::create(
            accountId: $account->id(),
            expenseType: $type,
        );

        $this->entityManager->persist($account);
        $this->entityManager->persist($type);
        $this->entityManager->persist($recurringExpense);
        $this->entityManager->flush();

        // 2. Define the payload for the new individual expense
        $newExpenseId = UuidMother::create();
        $payload = [
            'id' => $newExpenseId,
            'recurringExpenseId' => $recurringExpense->id(),
            'accountId' => $account->id(),
            'amount' => 15000,
            'date' => '2025-10-15',
        ];

        // 3. Send the PUT request
        $this->client->request(
            'PUT',
            '/api/v1/recurring-expenses/enter-monthly',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        // 4. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // 5. Assert that the new expense was created in the database
        $this->entityManager->clear();
        /** @var Expense|null $createdExpense */
        $createdExpense = $this->entityManager->find(Expense::class, $newExpenseId);

        $this->assertNotNull($createdExpense);
        $this->assertEquals($payload['amount'], $createdExpense->amount());
        $this->assertEquals($recurringExpense->id(), $createdExpense->recurringExpense()->id());
    }

    public function testItMapsExceptionsCorrectly(): void
    {
        $controller = $this->getContainer()->get(EnterMonthlyRecurringExpenseController::class);
        $exceptions = $controller->exceptions();

        $this->assertArrayHasKey(ResourceNotFoundException::class, $exceptions);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $exceptions[ResourceNotFoundException::class]);

        $this->assertArrayHasKey(InvalidDataException::class, $exceptions);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $exceptions[InvalidDataException::class]);
    }
}
