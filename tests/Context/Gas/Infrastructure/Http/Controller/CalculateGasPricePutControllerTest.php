<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Infrastructure\Http\Controller;

use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\Context\EventStore\Domain\StoredEventRepository;

final class CalculateGasPricePutControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_should_fail_without_the_correct_attribute(): void
    {
        $container = self::getContainer();



        // Define the JSON body for the request
        $requestBody = [
            'billAmountInCents' => 51000,
        ];

        // Make the HTTP request to our endpoint using the authenticated client
        $this->client->request(
            'PUT',
            '/api/v1/gas/price',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestBody)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Final assertion: check the event count in the repository
        /** @var StoredEventRepository $storedEventRepository */
        $storedEventRepository = $container->get(StoredEventRepository::class);
        $events = $storedEventRepository->findByEventType('gas.price.was.defined');

        $this->assertCount(1, $events);
        $this->assertEquals('gas.price.was.defined', $events[0]->eventType());
    }
}