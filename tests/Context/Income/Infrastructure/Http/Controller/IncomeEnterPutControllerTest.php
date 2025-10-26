<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Infrastructure\Http\Controller;

use App\Tests\Context\Income\Domain\IncomeTypeMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Response;

final class IncomeEnterPutControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_should_enter_income_and_store_event(): void
    {
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

        $this->client->request(
            'PUT',
            '/api/v1/incomes/enter',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }
}
