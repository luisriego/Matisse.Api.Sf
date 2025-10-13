<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Domain;

use App\Context\Income\Domain\Bus\IncomeWasEntered;
use App\Context\Income\Domain\Income;
use App\Context\Income\Domain\IncomeType;
use App\Context\Income\Domain\ValueObject\IncomeAmount;
use App\Context\Income\Domain\ValueObject\IncomeDueDate;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\DueDateMustBeInTheFutureException;
use DateTime;
use PHPUnit\Framework\TestCase;

class IncomeTest extends TestCase
{
    public function test_it_should_create_an_income(): void
    {
        $income = IncomeMother::create();

        $this->assertInstanceOf(Income::class, $income);
        $this->assertNotEmpty($income->id());
        $this->assertNotEmpty($income->amount());
        $this->assertNotNull($income->residentUnit());
        $this->assertNotNull($income->incomeType());
        $this->assertNotNull($income->dueDate());
        $this->assertTrue($income->isActive());
        $this->assertNotNull($income->createdAt());

        $events = $income->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(IncomeWasEntered::class, $events[0]);
        $this->assertSame($income->id(), $events[0]->aggregateId());
    }

    public function test_it_should_create_an_income_with_description(): void
    {
        $description = 'Test Description';
        $income = IncomeMother::create(description: $description);

        $this->assertInstanceOf(Income::class, $income);
        $this->assertSame($description, $income->description());

        $events = $income->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(IncomeWasEntered::class, $events[0]);
        $this->assertSame($description, $events[0]->toPrimitives()['description']);
    }

    public function test_it_should_throw_exception_if_amount_is_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Income amount must be zero or greater. Got: -100');

        IncomeMother::create(amount: new IncomeAmount(-100));
    }

    public function test_it_should_throw_exception_if_due_date_is_in_the_past(): void
    {
        $this->expectException(DueDateMustBeInTheFutureException::class);

        IncomeMother::create(dueDate: new IncomeDueDate(new DateTime('-1 day')));
    }

    public function test_it_should_set_income_type(): void
    {
        $income = IncomeMother::create();
        $newType = new IncomeType('new-type-id', 'NEW_CODE', 'New Type Name');

        $income->setIncomeType($newType);

        $this->assertSame($newType, $income->incomeType());
    }

    public function test_it_should_update_due_date(): void
    {
        $income = IncomeMother::create();
        $newDueDate = new DateTime('+10 days');

        $income->updateDueDate($newDueDate);

        $this->assertSame($newDueDate, $income->dueDate());
    }

    public function test_it_should_update_description(): void
    {
        $income = IncomeMother::create();
        $newDescription = 'Updated Description';

        $income->updateDescription($newDescription);

        $this->assertSame($newDescription, $income->description());
    }

    public function test_it_should_return_array_representation(): void
    {
        $income = IncomeMother::create();
        $incomeArray = $income->toArray();

        $this->assertIsArray($incomeArray);
        $this->assertArrayHasKey('id', $incomeArray);
        $this->assertArrayHasKey('amount', $incomeArray);
        $this->assertArrayHasKey('dueDate', $incomeArray);
        $this->assertArrayHasKey('paidAt', $incomeArray);
        $this->assertArrayHasKey('residentUnit', $incomeArray);
        $this->assertArrayHasKey('description', $incomeArray);

        $this->assertSame($income->id(), $incomeArray['id']);
        $this->assertSame($income->amount(), $incomeArray['amount']);
        $this->assertSame($income->dueDate(), $incomeArray['dueDate']);
        $this->assertSame($income->paidAt(), $incomeArray['paidAt']);
        $this->assertSame($income->residentUnit()->unit(), $incomeArray['residentUnit']);
        $this->assertSame($income->description(), $incomeArray['description']);
    }
}
