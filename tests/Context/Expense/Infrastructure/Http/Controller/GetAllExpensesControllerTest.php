<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Infrastructure\Http\Controller\GetAllExpensesController;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \App\Context\Expense\Infrastructure\Http\Controller\GetAllExpensesController
 */
final class GetAllExpensesControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_should_return_all_expenses(): void
    {
        // 1. Create unique dependencies for each expense, ensuring unique IDs
        $account1 = AccountMother::create();
        $type1 = ExpenseTypeMother::create(id: UuidMother::create());
        $expense1 = ExpenseMother::create(account: $account1, type: $type1);

        $account2 = AccountMother::create();
        $type2 = ExpenseTypeMother::create(id: UuidMother::create());
        $expense2 = ExpenseMother::create(account: $account2, type: $type2);

        // 2. Persist everything
        $this->entityManager->persist($account1);
        $this->entityManager->persist($type1);
        $this->entityManager->persist($expense1);
        
        $this->entityManager->persist($account2);
        $this->entityManager->persist($type2);
        $this->entityManager->persist($expense2);

        $this->entityManager->flush();

        // 3. Send the GET request
        $this->client->request('GET', '/api/v1/expenses');

        // 4. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        // 5. Assert the content of the response
        $this->assertIsArray($data);
        $this->assertArrayHasKey('expenses', $data);
        $this->assertArrayHasKey('qtd', $data);
        $this->assertEquals(2, $data['qtd']);

        $expensesInResponse = $data['expenses'];
        $this->assertCount(2, $expensesInResponse);

        // Check that the IDs of the created expenses are in the response
        $responseIds = array_column($expensesInResponse, 'id');
        $this->assertContains($expense1->id(), $responseIds);
        $this->assertContains($expense2->id(), $responseIds);
    }

    public function test_it_maps_exceptions_correctly(): void
    {
        $controller = $this->getContainer()->get(GetAllExpensesController::class);
        $exceptions = $controller->exceptions();

        $this->assertIsArray($exceptions);
        $this->assertEmpty($exceptions);
    }
}
