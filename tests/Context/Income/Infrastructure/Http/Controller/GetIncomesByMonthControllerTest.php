<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Infrastructure\Http\Controller;

use App\Context\Income\Domain\ValueObject\IncomeAmount;
use App\Context\Income\Domain\ValueObject\IncomeDueDate;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Context\Income\Domain\IncomeAmountMother;
use App\Tests\Context\Income\Domain\IncomeMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use DateTime;
use Doctrine\ORM\Exception\ORMException;

class GetIncomesByMonthControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    /** @test */
    public function test_it_should_return_incomes_for_a_given_month(): void
    {
        $targetDate = new DateTime('first day of next month');
        $year = (int)$targetDate->format('Y');
        $month = (int)$targetDate->format('m');

        $residentUnit = ResidentUnitMother::create();
        $this->entityManager->persist($residentUnit);

        $this->createAndPersistIncome(
            amount: IncomeAmountMother::create(50000), residentUnit: $residentUnit, dueDate: new IncomeDueDate((clone $targetDate)->setDate($year, $month, 5))
        );
        $this->createAndPersistIncome(
            amount: IncomeAmountMother::create(75000), residentUnit: $residentUnit, dueDate: new IncomeDueDate((clone $targetDate)->setDate($year, $month, 20))
        );
        $this->createAndPersistIncome(
            amount: IncomeAmountMother::create(100000), residentUnit: $residentUnit, dueDate: new IncomeDueDate((clone $targetDate)->setDate($year, $month + 1, 15))
        );

        $this->entityManager->flush();

        $this->client->request('GET', sprintf('/api/v1/incomes?year=%d&month=%d', $year, $month));

        $this->assertResponseIsSuccessful();
        $responseContent = $this->client->getResponse()->getContent();
        $incomes = json_decode($responseContent, true);

        self::assertCount(3, $incomes);
    }

    /** @test */
    public function test_it_should_return_empty_array_if_no_incomes(): void
    {
        $this->client->request('GET', '/api/v1/incomes?year=2030&month=1');

        $this->assertResponseIsSuccessful();
        $responseContent = $this->client->getResponse()->getContent();
        $incomes = json_decode($responseContent, true);

        self::assertCount(0, $incomes);
    }

    /**
     * @throws ORMException
     */
    private function createAndPersistIncome(
        ?IncomeAmount $amount = null,
        ?ResidentUnit $residentUnit = null,
        ?IncomeDueDate $dueDate = null
    ): void {
        $income = IncomeMother::create(id: null, amount: $amount, residentUnit: $residentUnit, type: null, dueDate: $dueDate, description: null);
        $this->entityManager->persist($income->incomeType());
        $this->entityManager->persist($income);
    }
}
