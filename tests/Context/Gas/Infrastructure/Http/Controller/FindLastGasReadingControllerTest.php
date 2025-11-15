<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Infrastructure\Http\Controller;

use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class FindLastGasReadingControllerTest extends ApiTestCase
{
    private ?StoredEventRepository $storedEventRepository;
    protected ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
        
        $container = self::getContainer();
        $this->storedEventRepository = $container->get(StoredEventRepository::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $events = $this->storedEventRepository->findByEventType('gas.reading.was.recorded');
        foreach ($events as $event) {
            $this->entityManager->remove($event);
        }
        $this->entityManager->flush();
    }

    public function test_it_should_return_the_last_reading(): void
    {
        $targetUnitId = Uuid::random()->value();
        $correctReading = 123.45;
        $futureReading = 999.99;

        $eventInPeriod = StoredEvent::create(
            Uuid::random()->value(),
            'gas.reading.was.recorded',
            [
                'residentUnitId' => $targetUnitId,
                'year' => (int)(new DateTimeImmutable('-3 month'))->format('Y'),
                'month' => (int)(new DateTimeImmutable('-3 month'))->format('n'),
                'reading' => $correctReading
            ]
        );
        $this->storedEventRepository->save($eventInPeriod);

        $eventOutOfPeriod = StoredEvent::create(
            Uuid::random()->value(),
            'gas.reading.was.recorded',
            [
                'residentUnitId' => $targetUnitId,
                'year' => (int)(new DateTimeImmutable('-1 month'))->format('Y'),
                'month' => (int)(new DateTimeImmutable('-1 month'))->format('n'),
                'reading' => $futureReading
            ]
        );
        $this->storedEventRepository->save($eventOutOfPeriod);

        $this->client->request('GET', '/api/v1/gas/resident-units/' . $targetUnitId . '/last-reading');

        $this->assertResponseIsSuccessful();
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame($targetUnitId, $responseContent['resident_unit_id']);
        $this->assertSame($correctReading, $responseContent['reading']);
    }

    protected function tearDown(): void
    {
        $this->storedEventRepository = null;
        $this->entityManager = null;
        parent::tearDown();
    }
}
