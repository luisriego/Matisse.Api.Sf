<?php

declare(strict_types=1);

namespace App\Tests\Context\Forecast\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\EnterExpense\EnterMonthlyRecurringExpenseCommand;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDay;
use App\Context\Expense\Domain\ValueObject\ExpenseEndDate;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Context\Expense\Domain\ValueObject\ExpenseStartDate;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Context\Forecast\Infrastructure\Scenario\CondominiumJan2026Scenario;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

final class ExpectedExpensesGetControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_returns_empty_list_when_no_expected_expenses_exist(): void
    {
        $this->client->request('GET', '/api/v1/expected-expenses');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame([], $data['data']);
    }

    public function test_it_returns_expected_expense_catalog_after_ofx_option_b(): void
    {
        $commandBus = self::getContainer()->get(MessageBusInterface::class);
        $scenario = CondominiumJan2026Scenario::seed($this->entityManager, $commandBus);

        $recurring = RecurringExpense::create(
            new ExpenseId(UuidMother::create()),
            $scenario->ledgerAccount->id(),
            new ExpenseAmount(27_306),
            $scenario->expenseType,
            new ExpenseDueDay(14),
            range(1, 12),
            ExpenseStartDate::from('2026-01-01'),
            ExpenseEndDate::from('2099-12-31'),
            'Cemig',
            null,
            false,
        );
        $this->entityManager->persist($recurring);
        $this->entityManager->flush();

        $commandBus->dispatch(new EnterMonthlyRecurringExpenseCommand(
            UuidMother::create(),
            $recurring->id(),
            $scenario->ledgerAccount->id(),
            27_306,
            '2026-01-14',
        ));

        $this->client->request('GET', '/api/v1/expected-expenses');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $data['data']);
        $this->assertSame('Cemig', $data['data'][0]['displayName']);
        $this->assertSame('variable', $data['data'][0]['amountKind']);
        $this->assertSame('monthly', $data['data'][0]['frequency']);
        $this->assertSame(27_306, $data['data'][0]['lastAmountCents']);
        $this->assertSame('2026-01', $data['data'][0]['lastReconciledMonth']);
        $this->assertTrue($data['data'][0]['isActive']);
    }

    public function test_it_filters_by_year(): void
    {
        $account = AccountMother::create();
        $type = ExpenseTypeMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->persist($type);

        $in2025 = RecurringExpense::create(
            new ExpenseId(UuidMother::create()),
            $account->id(),
            new ExpenseAmount(10_000),
            $type,
            new ExpenseDueDay(10),
            range(1, 12),
            new ExpenseStartDate(new DateTime('2025-01-01')),
            new ExpenseEndDate(new DateTime('2025-12-31')),
            'Copasa 2025',
        );
        $in2026 = RecurringExpense::create(
            new ExpenseId(UuidMother::create()),
            $account->id(),
            new ExpenseAmount(20_000),
            $type,
            new ExpenseDueDay(10),
            range(1, 12),
            new ExpenseStartDate(new DateTime('2026-01-01')),
            new ExpenseEndDate(new DateTime('2099-12-31')),
            'Cemig 2026',
        );
        $this->entityManager->persist($in2025);
        $this->entityManager->persist($in2026);
        $this->entityManager->flush();

        $this->client->request('GET', '/api/v1/expected-expenses?year=2026');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $data['data']);
        $this->assertSame('Cemig 2026', $data['data'][0]['displayName']);
    }

    public function test_it_returns_bad_request_for_invalid_year(): void
    {
        $this->client->request('GET', '/api/v1/expected-expenses?year=abc');
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
