<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Infrastructure\Http\Controller;

use App\Context\Income\Domain\Bus\IncomeWasEntered;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Context\Income\Domain\IncomeAmountMother;
use App\Tests\Context\Income\Domain\IncomeTypeMother;
use App\Tests\Context\Shared\Infrastructure\Persistence\InMemoryStoredEventRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GetIncomesByMonthControllerTest extends WebTestCase
{
    private InMemoryStoredEventRepository $eventRepository;

    protected function setUp(): void
    {
        parent::setUp();
        // Siempre asignar un valor, incluso si es un dummy, antes de cualquier potencial punto de fallo
        $this->eventRepository = new InMemoryStoredEventRepository(); // Inicializar con una instancia real

        try {
            $client = static::createClient();
            $container = $client->getContainer();
            // Reemplazar el dummy con el servicio real del contenedor
            $this->eventRepository = $container->get(InMemoryStoredEventRepository::class);
        } catch (\Throwable $e) {
            // Si ocurre una excepción, la instancia dummy seguirá estando ahí,
            // previniendo el error de "propiedad no inicializada" en tearDown.
            // Relanzar la excepción original para reportar el fallo real.
            throw $e;
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Limpiar el repositorio, que está garantizado que ha sido inicializado
        $this->eventRepository->clear();
    }

    /** @test */
    public function test_it_should_return_incomes_for_a_given_month(): void
    {
        $client = static::createClient();
        $accountId = Uuid::random()->value();
        $year = 2025;
        $month = 10;

        // Arrange: Populate the repository with test events
        $this->eventRepository->append(
            new IncomeWasEntered(
                Uuid::random()->value(),
                $accountId,
                IncomeTypeMother::create()->id(),
                IncomeAmountMother::create(50000)->value(),
                (new DateTimeImmutable("$year-$month-05"))->format('Y-m-d H:i:s'),
                'Income 1'
            )
        );
        $this->eventRepository->append(
            new IncomeWasEntered(
                Uuid::random()->value(),
                $accountId,
                IncomeTypeMother::create()->id(),
                IncomeAmountMother::create(75000)->value(),
                (new DateTimeImmutable("$year-$month-20"))->format('Y-m-d H:i:s'),
                'Income 2'
            )
        );
        // Add an event from another month to ensure it's filtered out
        $this->eventRepository->append(
            new IncomeWasEntered(
                Uuid::random()->value(),
                $accountId,
                IncomeTypeMother::create()->id(),
                IncomeAmountMother::create(100000)->value(),
                (new DateTimeImmutable("$year-11-15"))->format('Y-m-d H:i:s'),
                'Income from another month'
            )
        );

        // Act: Make a request to the endpoint
        $client->request('GET', sprintf('/api/v1/incomes?year=%d&month=%d', $year, $month));

        // Assert
        $this->assertResponseIsSuccessful();
        $responseContent = $client->getResponse()->getContent();
        $incomes = json_decode($responseContent, true);

        self::assertIsArray($incomes);
        self::assertCount(2, $incomes);

        // Check that the amounts are correct
        $amounts = array_column($incomes, 'amount');
        self::assertContains(50000, $amounts);
        self::assertContains(75000, $amounts);
        self::assertNotContains(100000, $amounts);
    }

    /** @test */
    public function test_it_should_return_empty_array_if_no_incomes(): void
    {
        $client = static::createClient();

        // Act: Make a request to the endpoint for a month with no incomes
        $client->request('GET', '/api/v1/incomes?year=2030&month=1');

        // Assert
        $this->assertResponseIsSuccessful();
        $responseContent = $client->getResponse()->getContent();
        $incomes = json_decode($responseContent, true);

        self::assertIsArray($incomes);
        self::assertCount(0, $incomes);
    }
}
