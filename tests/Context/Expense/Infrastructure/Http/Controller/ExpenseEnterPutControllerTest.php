<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Infrastructure\Http\Controller;

use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ExpenseEnterPutControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_should_enter_expense_and_store_event(): void
    {
        $expenseId = UuidMother::create();
        $account = AccountMother::create();
        $this->entityManager->persist($account);
        $expenseType = ExpenseTypeMother::create();
        $this->entityManager->persist($expenseType);
        $this->entityManager->flush();

        $payload = [
            'id' => $expenseId,
            'amount' => 1000,
            'type' => $expenseType->id(),
            'accountId' => $account->id(),
            'dueDate' => '2025-01-01',
            'isActive' => true,
            'description' => 'Test Expense Description',
        ];

        $this->client->request(
            'PUT',
            '/api/v1/expenses/enter',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }
}
