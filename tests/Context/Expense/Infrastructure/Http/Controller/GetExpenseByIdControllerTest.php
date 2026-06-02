<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Account\Domain\Exception\ExpenseNotFoundException;
use App\Context\Expense\Infrastructure\Http\Controller\GetExpenseByIdController;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;

/**
 * @covers \App\Context\Expense\Infrastructure\Http\Controller\GetExpenseByIdController
 */
final class GetExpenseByIdControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function testItShouldReturnExpenseWhenFound(): void
    {
        // 1. Create an expense to be found
        $expense = ExpenseMother::create();
        $this->entityManager->persist($expense->account());
        $this->entityManager->persist($expense->type());
        $this->entityManager->persist($expense);
        $this->entityManager->flush();

        // 2. Send the GET request
        $this->client->request('GET', '/api/v1/expenses/' . $expense->id());

        // 3. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        $this->assertIsArray($data);
        $this->assertEquals($expense->id(), $data['id']);
        $this->assertEquals($expense->amount(), $data['amount']);
        $this->assertEquals($expense->description(), $data['description']);
    }

    public function testItShouldReturnNotFoundWhenExpenseDoesNotExist(): void
    {
        // 1. Generate a random non-existent ID
        $nonExistentId = UuidMother::create();

        // 2. Send the GET request
        $this->client->request('GET', '/api/v1/expenses/' . $nonExistentId);

        // 3. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testItMapsExceptionsCorrectly(): void
    {
        $controller = $this->getContainer()->get(GetExpenseByIdController::class);
        $exceptions = $controller->exceptions();

        $this->assertArrayHasKey(ExpenseNotFoundException::class, $exceptions);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $exceptions[ExpenseNotFoundException::class]);
    }
}
