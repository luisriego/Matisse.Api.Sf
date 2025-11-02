<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Infrastructure\Http\Controller;

use App\Context\EventStore\Domain\StoredEventRepository;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class RecordGasConsumptionPutControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_should_record_gas_consumption(): void
    {
        $container = self::getContainer();

        // Define the JSON body for the request
        $requestBody = [
            'id' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            'residentUnitId' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a12',
            'year' => 2024,
            'month' => 11,
            'consumption' => 25.5,
        ];

        // Make the HTTP request to our endpoint using the authenticated client
        $this->client->request(
            'PUT',
            '/api/v1/gas/consumption',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestBody)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Final assertion: check the event count in the repository
        /** @var StoredEventRepository $storedEventRepository */
        $storedEventRepository = $container->get(StoredEventRepository::class);
        $events = $storedEventRepository->findByEventType('gas.consumption.was.recorded');

        $this->assertCount(1, $events);
        $this->assertEquals('gas.consumption.was.recorded', $events[0]->eventType());
    }
}