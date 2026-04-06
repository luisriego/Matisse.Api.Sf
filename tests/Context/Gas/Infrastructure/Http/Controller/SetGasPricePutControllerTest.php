<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Infrastructure\Http\Controller;

use App\Context\EventStore\Domain\StoredEventRepository;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class SetGasPricePutControllerTest extends ApiTestCase
{
    private ?StoredEventRepository $storedEventRepository = null;
    protected ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();

        $container = self::getContainer();
        $this->storedEventRepository = $container->get(StoredEventRepository::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $events = $this->storedEventRepository->findByEventType('gas.price.was.defined');
        foreach ($events as $event) {
            $this->entityManager->remove($event);
        }
        $this->entityManager->flush();
    }

    public function test_it_should_set_direct_gas_price_and_return_created(): void
    {
        $pricePerM3InCents = 2600;

        $this->client->request(
            'PUT',
            '/api/v1/gas/price/direct',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['pricePerM3InCents' => $pricePerM3InCents], JSON_THROW_ON_ERROR),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $events = $this->storedEventRepository->findByEventType('gas.price.was.defined');
        $this->assertCount(1, $events);
        $this->assertSame($pricePerM3InCents, $events[0]->payload()['pricePerM3InCents']);
    }

    protected function tearDown(): void
    {
        $this->storedEventRepository = null;
        $this->entityManager = null;
        parent::tearDown();
    }
}
