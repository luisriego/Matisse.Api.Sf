<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Account\Domain\Account;
use App\Context\EventStore\Domain\StoredEvent;
use App\Context\Expense\Domain\ExpenseType;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Response;

final class ExpenseEnterPutControllerTest extends ApiTestCase
{
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function test_it_should_enter_expense_and_store_event(): void
    {
        // Arrange: Create necessary entities and persist them
        $expenseId = UuidMother::create();

        /** @var Account $account */
        $account = AccountMother::create();
        $this->entityManager->persist($account);

        /** @var ExpenseType $expenseType */
        $expenseType = ExpenseTypeMother::create();
        $this->entityManager->persist($expenseType);

        $this->entityManager->flush(); // Flush to make sure Account and ExpenseType are in DB

        $payload = [
            'id' => $expenseId,
            'amount' => 1000,
            'type' => $expenseType->id(),
            'accountId' => $account->id(),
            'dueDate' => '2025-01-01',
            'isActive' => true,
            'description' => 'Test Expense Description',
        ];

        // Act: Make the API request
        $this->client->request(
            'PUT',
            '/api/v1/expenses/enter',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // Assert: Check HTTP response
        if (Response::HTTP_CREATED !== $this->client->getResponse()->getStatusCode()) {
            self::fail(
                sprintf(
                    'Expected HTTP status code %d but got %d. Response: %s',
                    Response::HTTP_CREATED,
                    $this->client->getResponse()->getStatusCode(),
                    $this->client->getResponse()->getContent()
                )
            );
        }
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        // Assert: Verify the event was stored in the event_store
        $storedEvent = $this->entityManager->getRepository(StoredEvent::class)->findOneBy([
            'aggregateId' => $expenseId,
            'eventType' => 'expense.entered',
        ]);

        self::assertNotNull($storedEvent, 'The ExpenseWasEntered event should be stored in the event_store.');
        self::assertSame($expenseId, $storedEvent->aggregateId());
        self::assertSame('expense.entered', $storedEvent->eventType());
        self::assertIsArray($storedEvent->payload()); // <-- CAMBIADO DE body() A payload()
        self::assertSame(1000, $storedEvent->payload()['amount']); // <-- CAMBIADO DE body() A payload()
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->entityManager !== null) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }
}