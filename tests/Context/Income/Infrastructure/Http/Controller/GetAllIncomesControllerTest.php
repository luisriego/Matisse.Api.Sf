<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Infrastructure\Http\Controller;

use App\Context\Income\Domain\ValueObject\IncomeDueDate;
use App\Tests\Context\Income\Domain\IncomeMother;
use App\Tests\Context\Income\Domain\IncomeTypeMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use DateTime;

class GetAllIncomesControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    /** @test */
    public function test_it_should_return_all_incomes(): void
    {
        $residentUnit = ResidentUnitMother::create();
        $this->entityManager->persist($residentUnit);
        $incomeType = IncomeTypeMother::create();
        $this->entityManager->persist($incomeType);

        $income1 = IncomeMother::create(residentUnit: $residentUnit, type: $incomeType, dueDate: new IncomeDueDate(new DateTime('now')));
        $income2 = IncomeMother::create(residentUnit: $residentUnit, type: $incomeType, dueDate: new IncomeDueDate(new DateTime('+1 day')));
        $income3 = IncomeMother::create(residentUnit: $residentUnit, type: $incomeType, dueDate: new IncomeDueDate(new DateTime('+2 day')));

        $this->entityManager->persist($income1);
        $this->entityManager->persist($income2);
        $this->entityManager->persist($income3);
        $this->entityManager->flush();

        $this->client->request('GET', '/api/v1/incomes');

        $this->assertResponseIsSuccessful();
        $responseContent = $this->client->getResponse()->getContent();
        $incomes = json_decode($responseContent, true);

        self::assertCount(3, $incomes);
    }

    /** @test */
    public function test_it_should_return_empty_array_if_no_incomes(): void
    {
        $this->client->request('GET', '/api/v1/incomes');

        $this->assertResponseIsSuccessful();
        $responseContent = $this->client->getResponse()->getContent();
        $incomes = json_decode($responseContent, true);

        self::assertCount(0, $incomes);
    }
}
