<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Infrastructure\Http\Controller;

use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class FindGasReadingControllerTest extends ApiTestCase
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

    public function test_it_should_return_the_reading_for_a_specific_period(): void
    {
        $targetUnitId = Uuid::random()->value();
        $targetYear = 2025;
        $targetMonth = 10;
        $correctReading = 123.45;

        $event = StoredEvent::create(
            Uuid::random()->value(),
            'gas.reading.was.recorded',
            [
                'residentUnitId' => $targetUnitId,
                'year' => $targetYear,
                'month' => $targetMonth,
                'reading' => $correctReading
            ]
        );
        $this->storedEventRepository->save($event);

        // CORREGIDO: Usar la nueva ruta con sprintf
        $this->client->request('GET', sprintf('/api/v1/gas/resident-units/%s/reading/%d/%d', $targetUnitId, $targetYear, $targetMonth));

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
