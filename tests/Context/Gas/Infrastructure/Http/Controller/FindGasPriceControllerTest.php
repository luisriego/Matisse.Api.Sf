<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Infrastructure\Http\Controller;

use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class FindGasPriceControllerTest extends ApiTestCase
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

        // Limpiar eventos de gas antes de cada test para evitar interferencias
        $events = $this->storedEventRepository->findByEventType('gas.price.was.defined');
        foreach ($events as $event) {
            $this->entityManager->remove($event);
        }
        $this->entityManager->flush();
    }

    public function test_it_should_return_the_gas_price_when_it_exists(): void
    {
        // Arrange
        $priceAsFloat = 5.87;
        $expectedPriceInCents = 587;

        $storedEvent = StoredEvent::create(
            Uuid::random()->value(),
            'gas.price.was.defined',
            ['pricePerM3' => $priceAsFloat]
        );
        $this->storedEventRepository->save($storedEvent);

        // Act
        $this->client->request('GET', '/api/v1/gas/price');

        // Assert
        $this->assertResponseIsSuccessful();

        $responseContent = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('price_per_m3_in_cents', $responseContent);
        $this->assertSame($expectedPriceInCents, $responseContent['price_per_m3_in_cents']);
    }

    protected function tearDown(): void
    {
        $this->storedEventRepository = null;
        $this->entityManager = null;
        parent::tearDown();
    }
}
