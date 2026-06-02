<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Infrastructure\Http\Controller;

use App\Context\EventStore\Domain\StoredEventRepository;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;
use function json_encode;

final class ExpenseEnterPutControllerTest extends ApiTestCase
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
    public function testItShouldEnterExpenseAndStoreEvent(): void
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
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // --- Strong Assertions for the API Response ---
        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        // 1. Assert that the expected keys exist
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('amount', $data);
        $this->assertArrayHasKey('dueDate', $data);
        $this->assertArrayHasKey('type', $data);
        $this->assertIsArray($data['type']);
        $this->assertArrayHasKey('name', $data['type']);

        // 2. Assert values and formats
        $this->assertSame($payload['id'], $data['id']);
        $this->assertSame(1000, $data['amount']);
        $this->assertSame('Test Expense Description', $data['description']);

        // 3. Assert the exact format of the date
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $data['dueDate'], 'The dueDate format is not YYYY-MM-DD');

        // 4. Assert the value of the nested object
        $this->assertSame($expenseType->name(), $data['type']['name']);
        // --- End of Strong Assertions ---

        $container = self::getContainer();

        /** @var StoredEventRepository $storedEventRepository */
        $storedEventRepository = $container->get(StoredEventRepository::class);
        $events = $storedEventRepository->findByEventType('expense.entered');

        $this->assertCount(1, $events);
        $this->assertEquals('expense.entered', $events[0]->eventType());
    }
}
