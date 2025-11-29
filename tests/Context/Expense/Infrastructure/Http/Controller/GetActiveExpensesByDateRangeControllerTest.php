<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Infrastructure\Http\Controller\GetActiveExpensesByDateRangeController;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \App\Context\Expense\Infrastructure\Http\Controller\GetActiveExpensesByDateRangeController
 */
final class GetActiveExpensesByDateRangeControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_should_return_only_active_expenses_in_date_range(): void
    {
        // 1. Create expenses for the test scenario
        $year = 2025;
        $month = 10;

        // Expense that should be found
        $account1 = AccountMother::create();
        $type1 = ExpenseTypeMother::create(id: UuidMother::create());
        $activeExpenseInOctober = ExpenseMother::create(
            account: $account1,
            type: $type1,
            dueDate: new DateTime("$year-$month-15")
        );

        // Expenses that should NOT be found
        $account2 = AccountMother::create();
        $type2 = ExpenseTypeMother::create(id: UuidMother::create());
        $activeExpenseInNovember = ExpenseMother::create(
            account: $account2,
            type: $type2,
            dueDate: new DateTime("$year-11-15")
        );

        $account3 = AccountMother::create();
        $type3 = ExpenseTypeMother::create(id: UuidMother::create());
        $inactiveExpenseInOctober = ExpenseMother::createInactive(
            account: $account3,
            type: $type3,
            dueDate: new DateTime("$year-$month-20")
        );

        // 2. Persist all entities
        $this->entityManager->persist($account1);
        $this->entityManager->persist($type1);
        $this->entityManager->persist($activeExpenseInOctober);

        $this->entityManager->persist($account2);
        $this->entityManager->persist($type2);
        $this->entityManager->persist($activeExpenseInNovember);

        $this->entityManager->persist($account3);
        $this->entityManager->persist($type3);
        $this->entityManager->persist($inactiveExpenseInOctober);

        $this->entityManager->flush();

        // 3. Send the GET request
        $this->client->request('GET', "/api/v1/expenses/date-range/$year/$month");

        // 4. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        // 5. Assert the content of the response
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals($activeExpenseInOctober->id(), $data[0]['id']);
    }

    public function test_it_maps_exceptions_correctly(): void
    {
        $controller = $this->getContainer()->get(GetActiveExpensesByDateRangeController::class);
        $exceptions = $controller->exceptions();

        $this->assertIsArray($exceptions);
        $this->assertEmpty($exceptions);
    }
}
