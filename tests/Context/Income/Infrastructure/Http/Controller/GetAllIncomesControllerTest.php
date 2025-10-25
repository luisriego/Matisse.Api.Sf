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

class GetAllIncomesControllerTest extends WebTestCase
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
    public function test_it_should_return_all_incomes(): void
    {
        $client = static::createClient();
        $accountId = Uuid::random()->value();
        $incomeType = IncomeTypeMother::create();

        // Arrange: Populate the repository with test events
        $this->eventRepository->append(
            new IncomeWasEntered(
                Uuid::random()->value(),
                $accountId,
                $incomeType->id(),
                IncomeAmountMother::create(100000)->value(),
                (new DateTimeImmutable("2024-01-10"))->format('Y-m-d H:i:s'),
                'Income January'
            )
        );
        $this->eventRepository->append(
            new IncomeWasEntered(
                Uuid::random()->value(),
                $accountId,
                $incomeType->id(),
                IncomeAmountMother::create(200000)->value(),
                (new DateTimeImmutable("2024-02-15"))->format('Y-m-d H:i:s'),
                'Income February'
            )
        );
        $this->eventRepository->append(
            new IncomeWasEntered(
                Uuid::random()->value(),
                $accountId,
                $incomeType->id(),
                IncomeAmountMother::create(300000)->value(),
                (new DateTimeImmutable("2024-03-20"))->format('Y-m-d H:i:s'),
                'Income March'
            )
        );

        // Act: Make a request to the endpoint
        $client->request('GET', '/api/v1/incomes');

        // Assert
        $this->assertResponseIsSuccessful();
        $responseContent = $client->getResponse()->getContent();
        $incomes = json_decode($responseContent, true);

        self::assertIsArray($incomes);
        self::assertCount(3, $incomes);

        // Check that the amounts are correct
        $amounts = array_column($incomes, 'amount');
        self::assertContains(100000, $amounts);
        self::assertContains(200000, $amounts);
        self::assertContains(300000, $amounts);

        // Check that type is the full object
        self::assertIsArray($incomes[0]['type']);
        self::assertArrayHasKey('id', $incomes[0]['type']);
        self::assertArrayHasKey('name', $incomes[0]['type']);
        self::assertArrayHasKey('code', $incomes[0]['type']);
        self::assertArrayHasKey('description', $incomes[0]['type']);
    }

    /** @test */
    public function test_it_should_return_empty_array_if_no_incomes(): void
    {
        $client = static::createClient();

        // Act: Make a request to the endpoint when no incomes are present
        $client->request('GET', '/api/v1/incomes');

        // Assert
        $this->assertResponseIsSuccessful();
        $responseContent = $client->getResponse()->getContent();
        $incomes = json_decode($responseContent, true);

        self::assertIsArray($incomes);
        self::assertCount(0, $incomes);
    }
}
