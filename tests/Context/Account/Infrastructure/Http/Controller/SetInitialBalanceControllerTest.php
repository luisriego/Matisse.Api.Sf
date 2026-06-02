<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Infrastructure\Http\Controller\SetInitialBalanceController;
use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Shared\Domain\Exception\InvalidDataException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;
use function json_encode;

final class SetInitialBalanceControllerTest extends ApiTestCase
{
    private ?StoredEventRepository $storedEventRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
        $this->storedEventRepository = $this->getContainer()->get(StoredEventRepository::class);
    }

    protected function tearDown(): void
    {
        $this->storedEventRepository = null;
        parent::tearDown();
    }

    public function testItShouldSetInitialBalanceAndStoreEvent(): void
    {
        // 1. Create an account
        $account = AccountMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        // 2. Define the payload
        $payload = [
            'amount' => 50000, // e.g., 500.00
            'date' => '2025-01-01',
        ];

        // 3. Send the PUT request
        $this->client->request(
            'PUT',
            '/api/v1/accounts/' . $account->id() . '/initial-balance',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        // 4. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);

        // 5. Assert that the event was stored
        /** @var StoredEvent[] $events */
        $events = $this->storedEventRepository->findByEventType('account.initial_balance.set');

        $this->assertCount(1, $events);
        $event = $events[0];
        $eventBody = $event->payload();

        $this->assertEquals($account->id(), $event->aggregateId());
        $this->assertEquals($payload['amount'], $eventBody['amount']);
        $this->assertEquals($payload['date'], $eventBody['date']);
    }

    public function testItShouldReturnBadRequestIfAmountIsMissing(): void
    {
        $account = AccountMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        $payload = ['date' => '2025-01-01']; // Missing 'amount'

        $this->client->request(
            'PUT',
            '/api/v1/accounts/' . $account->id() . '/initial-balance',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('The fields "amount" and "date" are required.', $responseContent['message']);
    }

    public function testItShouldReturnBadRequestIfDateIsMissing(): void
    {
        $account = AccountMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        $payload = ['amount' => 50000]; // Missing 'date'

        $this->client->request(
            'PUT',
            '/api/v1/accounts/' . $account->id() . '/initial-balance',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('The fields "amount" and "date" are required.', $responseContent['message']);
    }

    public function testItMapsExceptionsCorrectly(): void
    {
        $controller = $this->getContainer()->get(SetInitialBalanceController::class);
        $exceptions = $controller->exceptions();

        $this->assertArrayHasKey(ResourceNotFoundException::class, $exceptions);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $exceptions[ResourceNotFoundException::class]);

        $this->assertArrayHasKey(InvalidDataException::class, $exceptions);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $exceptions[InvalidDataException::class]);
    }
}
