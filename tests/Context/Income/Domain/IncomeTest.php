<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Domain;

use App\Context\Income\Domain\Event\IncomeWasEntered;
use App\Context\Income\Domain\Income;
use App\Context\Income\Domain\IncomeType;
use App\Context\Income\Domain\ValueObject\IncomeAmount;
use App\Context\Income\Domain\ValueObject\IncomeDueDate;
use App\Shared\Domain\Exception\DueDateMustBeInTheFutureException;
use App\Shared\Domain\Exception\InvalidArgumentException;
use DateTime;
use PHPUnit\Framework\TestCase;

class IncomeTest extends TestCase
{
    public function testItShouldCreateAnIncome(): void
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

    public function testItShouldCreateAnIncomeWithDescription(): void
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

    public function testItShouldThrowExceptionIfAmountIsNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Income amount must be zero or greater. Got: -100');

        IncomeMother::create(amount: new IncomeAmount(-100));
    }

    public function testItShouldThrowExceptionIfDueDateIsInThePast(): void
    {
        $this->expectException(DueDateMustBeInTheFutureException::class);

        IncomeMother::create(dueDate: new IncomeDueDate(new DateTime('-1 day')));
    }

    public function testItShouldSetIncomeType(): void
    {
        $income = IncomeMother::create();
        $newType = new IncomeType('new-type-id', 'NEW_CODE', 'New Type Name');

        $income->categorizeAs($newType);

        $this->assertSame($newType, $income->incomeType());
    }

    public function testItShouldUpdateDueDate(): void
    {
        $income = IncomeMother::create();
        $newDueDate = new DateTime('+10 days');

        $income->changeDueDate($newDueDate);

        $this->assertSame($newDueDate, $income->dueDate());
    }

    public function testItShouldUpdateDescription(): void
    {
        $income = IncomeMother::create();
        $newDescription = 'Updated Description';

        $income->changeDescription($newDescription);

        $this->assertSame($newDescription, $income->description());
    }

    public function testItShouldReturnArrayRepresentation(): void
    {
        $income = IncomeMother::create();
        $incomeArray = $income->toArray();

        $this->assertIsArray($incomeArray);
        $this->assertArrayHasKey('id', $incomeArray);
        $this->assertArrayHasKey('amount', $incomeArray);
        $this->assertArrayHasKey('type', $incomeArray);
        $this->assertArrayHasKey('dueDate', $incomeArray);
        $this->assertArrayHasKey('paidAt', $incomeArray);
        $this->assertArrayHasKey('residentUnitId', $incomeArray);
        $this->assertArrayHasKey('description', $incomeArray);
        $this->assertArrayHasKey('account', $incomeArray);

        $this->assertSame($income->id(), $incomeArray['id']);
        $this->assertSame($income->amount(), $incomeArray['amount']);
        $this->assertSame($income->incomeType()->toArray(), $incomeArray['type']);
        $this->assertSame($income->dueDate()->format('Y-m-d'), $incomeArray['dueDate']);
        $this->assertSame($income->paidAt(), $incomeArray['paidAt']);
        $this->assertSame($income->residentUnit()->id(), $incomeArray['residentUnitId']);
        $this->assertSame($income->description(), $incomeArray['description']);
        $this->assertSame($income->accountId(), $incomeArray['account']['id'] ?? null);
    }
}
