<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Account\Domain\Account;
use App\Context\Account\Domain\AccountId;
use App\Context\Account\Domain\AccountName;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeCode;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeDescription;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeDistributionMethod;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeId;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeName;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class ExpenseTypeAccountRelationTest extends TestCase
{
    public function testExpenseTypeCanBeCreatedWithoutAccount(): void
    {
        $id = new ExpenseTypeId((string) Uuid::uuid4());
        $code = new ExpenseTypeCode('ELEC');
        $name = new ExpenseTypeName('Electricidad');
        $method = new ExpenseTypeDistributionMethod(ExpenseType::EQUAL);
        $description = new ExpenseTypeDescription('Gastos de electricidad');

        $expenseType = ExpenseType::create($id, $code, $name, $method, $description);

        $this->assertNull($expenseType->account());
        $this->assertEquals('ELEC', $expenseType->code());
        $this->assertEquals('Electricidad', $expenseType->name());
    }

    public function testExpenseTypeCanBeCreatedWithAccount(): void
    {
        $accountId = new AccountId((string) Uuid::uuid4());
        $account = Account::create($accountId, new AccountName('Cuenta Principal'));

        $id = new ExpenseTypeId((string) Uuid::uuid4());
        $code = new ExpenseTypeCode('WATER');
        $name = new ExpenseTypeName('Agua');
        $method = new ExpenseTypeDistributionMethod(ExpenseType::EQUAL);
        $description = new ExpenseTypeDescription('Gastos de agua');

        $expenseType = ExpenseType::create($id, $code, $name, $method, $description, $account);

        $this->assertNotNull($expenseType->account());
        $this->assertEquals($account, $expenseType->account());
        $this->assertEquals($accountId->value(), $expenseType->account()->id());
    }

    public function testAccountCanHaveMultipleExpenseTypes(): void
    {
        $accountId = new AccountId((string) Uuid::uuid4());
        $account = Account::create($accountId, new AccountName('Cuenta Secundaria'));

        $type1 = ExpenseType::create(
            new ExpenseTypeId((string) Uuid::uuid4()),
            new ExpenseTypeCode('ELEC'),
            new ExpenseTypeName('Electricidad'),
            new ExpenseTypeDistributionMethod(ExpenseType::EQUAL),
            new ExpenseTypeDescription('Gastos de electricidad'),
            $account,
        );

        $type2 = ExpenseType::create(
            new ExpenseTypeId((string) Uuid::uuid4()),
            new ExpenseTypeCode('WATER'),
            new ExpenseTypeName('Agua'),
            new ExpenseTypeDistributionMethod(ExpenseType::EQUAL),
            new ExpenseTypeDescription('Gastos de agua'),
            $account,
        );

        $account->addExpenseType($type1);
        $account->addExpenseType($type2);

        $this->assertCount(2, $account->expenseTypes());
        $this->assertTrue($account->expenseTypes()->contains($type1));
        $this->assertTrue($account->expenseTypes()->contains($type2));
    }

    public function testAccountCanRemoveExpenseType(): void
    {
        $accountId = new AccountId((string) Uuid::uuid4());
        $account = Account::create($accountId, new AccountName('Cuenta Tercera'));

        $type = ExpenseType::create(
            new ExpenseTypeId((string) Uuid::uuid4()),
            new ExpenseTypeCode('GAS'),
            new ExpenseTypeName('Gas'),
            new ExpenseTypeDistributionMethod(ExpenseType::INDIVIDUAL),
            new ExpenseTypeDescription('Gastos de gas'),
            $account,
        );

        $account->addExpenseType($type);
        $this->assertCount(1, $account->expenseTypes());

        $account->removeExpenseType($type);
        $this->assertCount(0, $account->expenseTypes());
    }
}

