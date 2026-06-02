<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Infrastructure\Http\Controller;

use App\Context\EventStore\Domain\StoredEventRepository;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

use function json_encode;

final class RecordGasReadingPutControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function testItShouldRecordGasReading(): void
    {
        $container = self::getContainer();

        $requestBody = [
            'id' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            'residentUnitId' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a12',
            'year' => 2024,
            'month' => 11,
            'reading' => 150.7,
        ];

        $this->client->request(
            'PUT',
            '/api/v1/gas/reading',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestBody),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var StoredEventRepository $storedEventRepository */
        $storedEventRepository = $container->get(StoredEventRepository::class);
        $events = $storedEventRepository->findByEventType('gas.reading.was.recorded');

        $this->assertCount(1, $events);
        $this->assertEquals('gas.reading.was.recorded', $events[0]->eventType());
    }
}
