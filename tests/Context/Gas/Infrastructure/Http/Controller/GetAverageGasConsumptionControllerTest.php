<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Infrastructure\Http\Controller;

use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class GetAverageGasConsumptionControllerTest extends ApiTestCase
{
    private ?StoredEventRepository $storedEventRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
        $this->storedEventRepository = self::getContainer()->get(StoredEventRepository::class);
    }

    public function test_it_should_return_the_average_gas_consumption(): void
    {
        // 1. Arrange: Create a series of gas reading events for a specific resident unit
        $residentUnitId = UuidMother::create();
        
        // Reading 1 (Month 1): 100
        $this->createAndPersistGasReadingEvent($residentUnitId, 2023, 1, 100.0);
        
        // Reading 2 (Month 2): 120 -> Consumption: 20
        $this->createAndPersistGasReadingEvent($residentUnitId, 2023, 2, 120.0);
        
        // Reading 3 (Month 3): 150 -> Consumption: 30
        $this->createAndPersistGasReadingEvent($residentUnitId, 2023, 3, 150.0);

        // Reading for another unit (should be ignored)
        $this->createAndPersistGasReadingEvent(UuidMother::create(), 2023, 1, 50.0);

        // Expected average: (20 + 30) / 2 = 25
        $expectedAverage = 25.0;

        // 2. Act: Make the API request
        $this->client->request('GET', '/api/v1/gas/resident-units/' . $residentUnitId . '/average-consumption');

        // 3. Assert: Check the response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        $this->assertArrayHasKey('averageMonthlyConsumption', $data);
        $this->assertEquals($expectedAverage, $data['averageMonthlyConsumption']);
    }

    public function test_it_should_return_404_when_not_enough_readings(): void
    {
        // 1. Arrange: Create only one reading
        $residentUnitId = UuidMother::create();
        $this->createAndPersistGasReadingEvent($residentUnitId, 2023, 1, 100.0);

        // 2. Act: Make the API request
        $this->client->request('GET', '/api/v1/gas/resident-units/' . $residentUnitId . '/average-consumption');

        // 3. Assert: Check for 404 Not Found
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function createAndPersistGasReadingEvent(string $residentUnitId, int $year, int $month, float $reading): void
    {
        $payload = [
            'residentUnitId' => $residentUnitId,
            'year' => $year,
            'month' => $month,
            'reading' => $reading,
        ];

        $storedEvent = StoredEvent::create(
            UuidMother::create(),
            'gas.reading.was.recorded',
            $payload // Pass the array directly
        );

        $this->storedEventRepository->save($storedEvent);
    }
}
