<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Infrastructure\Http\Controller;

use App\Context\Income\Domain\ValueObject\IncomeDueDate;
use App\Context\Income\Infrastructure\Http\Controller\GetIncomesByMonthController;
use App\Tests\Context\Income\Domain\IncomeMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \App\Context\Income\Infrastructure\Http\Controller\GetIncomesByMonthController
 */
final class GetIncomesByMonthControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_should_return_incomes_for_a_given_month(): void
    {
        // 1. Create incomes for the test scenario, using the current year to avoid past date errors
        $currentDate = new DateTime();
        $year = (int)$currentDate->format('Y');
        $month = (int)$currentDate->format('m');
        
        // To ensure we are testing a future month if we are at the end of the year
        if ($month >= 11) {
            $year += 1;
            $month = 1;
        }

        $testMonth = $month;
        $nextMonth = $month + 1;

        // Income that should be found
        $incomeInTestMonth = IncomeMother::create(
            dueDate: new IncomeDueDate(new DateTime("$year-$testMonth-15"))
        );

        // Income that should NOT be found
        $incomeInNextMonth = IncomeMother::create(
            dueDate: new IncomeDueDate(new DateTime("$year-$nextMonth-15"))
        );

        // 2. Persist all entities
        $this->entityManager->persist($incomeInTestMonth->residentUnit());
        $this->entityManager->persist($incomeInTestMonth->incomeType());
        $this->entityManager->persist($incomeInTestMonth);

        $this->entityManager->persist($incomeInNextMonth->residentUnit());
        $this->entityManager->persist($incomeInNextMonth->incomeType());
        $this->entityManager->persist($incomeInNextMonth);

        $this->entityManager->flush();

        // 3. Send the GET request
        $this->client->request('GET', "/api/v1/incomes/date-range/$year/$testMonth");

        // 4. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        // 5. Assert the content of the response
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals($incomeInTestMonth->id(), $data[0]['id']);
    }

    public function test_it_maps_exceptions_correctly(): void
    {
        $controller = $this->getContainer()->get(GetIncomesByMonthController::class);
        $exceptions = $controller->exceptions();

        $this->assertIsArray($exceptions);
        $this->assertEmpty($exceptions);
    }
}
