<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Infrastructure\Http\Controller;

use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use DateTimeImmutable;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\Response;

final class ExpenseEnterWithDescriptionPutControllerTest extends ApiTestCase
{
    private ?StoredEventRepository $storedEventRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
        $this->storedEventRepository = $this->getContainer()->get(StoredEventRepository::class);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function test_it_should_enter_expense_with_description_and_store_event(): void
    {
        // 1. Create necessary entities
        $account = AccountMother::create();
        $this->entityManager->persist($account);
        $expenseType = ExpenseTypeMother::create();
        $this->entityManager->persist($expenseType);
        $this->entityManager->flush();

        // 2. Define the payload
        $expenseId = UuidMother::create();
        $description = 'This is a detailed expense description.';
        $payload = [
            'id' => $expenseId,
            'amount' => 12500, // 125.00
            'type' => $expenseType->id(),
            'accountId' => $account->id(),
            'dueDate' => (new DateTimeImmutable('+5 days'))->format('Y-m-d'),
            'description' => $description,
        ];

        // 3. Send the PUT request
        $this->client->request(
            'PUT',
            '/api/v1/expenses/enter-description',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // 4. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // 5. Assert that the event was stored
        /** @var StoredEvent[] $events */
        $events = $this->storedEventRepository->findByEventType('expense.entered');

        $this->assertCount(1, $events);
        $event = $events[0];
        $eventBody = $event->payload();

        $this->assertEquals($expenseId, $event->aggregateId());
        $this->assertEquals($payload['amount'], $eventBody['amount']);
        $this->assertEquals($payload['description'], $eventBody['description']);
        $this->assertEquals($payload['accountId'], $eventBody['accountId']);
    }

    protected function tearDown(): void
    {
        $this->storedEventRepository = null;
        parent::tearDown();
    }
}
