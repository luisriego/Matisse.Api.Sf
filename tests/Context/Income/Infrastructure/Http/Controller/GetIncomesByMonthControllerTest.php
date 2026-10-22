<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Infrastructure\Http\Controller;

use App\Context\Income\Domain\ValueObject\IncomeDueDate;
use App\Context\Income\Infrastructure\Http\Controller\GetIncomesByMonthController;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Income\Domain\IncomeMother;
use App\Tests\Context\Income\Domain\IncomeTypeMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use App\Tests\Shared\Domain\UuidMother;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;
use function json_encode;

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

    public function testItShouldReturnIncomesForAGivenMonth(): void
    {
        // 1. Create incomes for the test scenario, using future dates to avoid past date errors
        $currentDate = new DateTime('+5 days');
        $year = (int) $currentDate->format('Y');
        $testMonth = (int) $currentDate->format('m');
        $day = (int) $currentDate->format('d');

        $nextMonthDate = (clone $currentDate)->modify('+1 month');
        $nextMonthYear = (int) $nextMonthDate->format('Y');
        $nextMonth = (int) $nextMonthDate->format('m');
        $accountInTestMonth = AccountMother::create();
        $accountInNextMonth = AccountMother::create();

        // Income that should be found
        $incomeInTestMonth = IncomeMother::create(
            accountId: $accountInTestMonth->id(),
            dueDate: new IncomeDueDate(new DateTime("{$year}-{$testMonth}-{$day}")),
        );

        // Income that should NOT be found
        $incomeInNextMonth = IncomeMother::create(
            accountId: $accountInNextMonth->id(),
            dueDate: new IncomeDueDate(new DateTime("{$nextMonthYear}-{$nextMonth}-15")),
        );

        // 2. Persist all entities
        $this->entityManager->persist($accountInTestMonth);
        $this->entityManager->persist($accountInNextMonth);
        $this->entityManager->persist($incomeInTestMonth->residentUnit());
        $this->entityManager->persist($incomeInTestMonth->incomeType());
        $this->entityManager->persist($incomeInTestMonth);

        $this->entityManager->persist($incomeInNextMonth->residentUnit());
        $this->entityManager->persist($incomeInNextMonth->incomeType());
        $this->entityManager->persist($incomeInNextMonth);

        $this->entityManager->flush();

        // 3. Send the GET request
        $this->client->request('GET', "/api/v1/incomes/date-range/{$year}/{$testMonth}");

        // 4. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        // 5. Assert the content of the response
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals($incomeInTestMonth->id(), $data[0]['id']);
    }

    public function testItShouldListEnteredIncomeWhenQueryingByMonth(): void
    {
        // 1. Prepare domain resources
        $residentUnit = ResidentUnitMother::create();
        $this->entityManager->persist($residentUnit);

        $incomeType = IncomeTypeMother::create();
        $this->entityManager->persist($incomeType);

        $account = AccountMother::create();
        $this->entityManager->persist($account);

        $this->entityManager->flush();

        // 2. Register an income via the PUT endpoint for a future month
        $futureDate = (new DateTime('+5 days'))->format('Y-m-d');
        $year = (int) (new DateTime($futureDate))->format('Y');
        $month = (int) (new DateTime($futureDate))->format('m');

        $incomeId = UuidMother::create();

        $this->client->request(
            'PUT',
            '/api/v1/incomes/enter',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'id' => $incomeId,
                'amount' => 5000,
                'type' => $incomeType->id(),
                'accountId' => $account->id(),
                'dueDate' => $futureDate,
                'residentUnitId' => $residentUnit->id(),
                'description' => 'Test income for MOV-04',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // 3. Query incomes by the same month via GET endpoint
        $this->client->request('GET', "/api/v1/incomes/date-range/{$year}/{$month}");

        // 4. Assert the response contains the entered income
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals($incomeId, $data[0]['id']);
        $this->assertEquals(5000, $data[0]['amount']);
    }

    public function testItMapsExceptionsCorrectly(): void
    {
        $controller = $this->getContainer()->get(GetIncomesByMonthController::class);
        $exceptions = $controller->exceptions();

        $this->assertIsArray($exceptions);
        $this->assertEmpty($exceptions);
    }
}
