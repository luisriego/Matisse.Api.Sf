<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Infrastructure\Http\Controller;

use App\Context\EventStore\Domain\StoredEvent;
use App\Context\Income\Domain\IncomeType;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Tests\Context\Income\Domain\IncomeTypeMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Response;

final class IncomeEnterPutControllerTest extends ApiTestCase
{
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function test_it_should_enter_income_and_store_event(): void
    {
        // Arrange:
        $incomeId = UuidMother::create();

        $residentUnit = ResidentUnitMother::create();
        $this->entityManager->persist($residentUnit);

        $incomeType = IncomeTypeMother::create();
        $this->entityManager->persist($incomeType);

        $this->entityManager->flush();

        $futureDate = (new DateTimeImmutable())->modify('+1 day')->format('Y-m-d');

        $payload = [
            'id' => $incomeId,
            'amount' => 5000,
            'residentUnitId' => $residentUnit->id(),
            'type' => $incomeType->id(),
            'dueDate' => $futureDate,
            'description' => 'Test Income Description',
        ];

        // Act:
        $this->client->request(
            'PUT',
            '/api/v1/incomes/enter',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // Assert:
        if (Response::HTTP_CREATED !== $this->client->getResponse()->getStatusCode()) {
            self::fail(
                sprintf(
                    'Expected HTTP status code %d but got %d. Response: %s',
                    Response::HTTP_CREATED,
                    $this->client->getResponse()->getStatusCode(),
                    $this->client->getResponse()->getContent()
                )
            );
        }
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        // Assert:
        $storedEvent = $this->entityManager->getRepository(StoredEvent::class)->findOneBy([
            'aggregateId' => $incomeId,
            'eventType' => 'income.entered',
        ]);

        self::assertNotNull($storedEvent, 'The IncomeWasEntered event should be stored in the event_store.');
        self::assertSame($incomeId, $storedEvent->aggregateId());
        self::assertSame('income.entered', $storedEvent->eventType());
        self::assertIsArray($storedEvent->payload());
        self::assertSame(5000, $storedEvent->payload()['amount']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->entityManager !== null) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }
}