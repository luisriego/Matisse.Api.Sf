<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Domain;

use App\Context\Income\Domain\IncomeType;
use PHPUnit\Framework\TestCase;

class IncomeTypeTest extends TestCase
{
    public function testItShouldCreateAnIncomeType(): void
    {
        $incomeType = IncomeTypeMother::create();

        $this->assertInstanceOf(IncomeType::class, $incomeType);
        $this->assertNotEmpty($incomeType->id());
        $this->assertNotEmpty($incomeType->name());
        $this->assertNotEmpty($incomeType->code());
        $this->assertNotEmpty($incomeType->description());
        $this->assertEmpty($incomeType->incomes());
    }

    public function testItShouldAddAnIncome(): void
    {
        $incomeType = IncomeTypeMother::create();
        $income = IncomeMother::create();

        $incomeType->addIncome($income);

        $this->assertCount(1, $incomeType->incomes());
        $this->assertTrue($incomeType->incomes()->contains($income));
    }

    public function testItShouldNotAddDuplicateIncome(): void
    {
        $incomeType = IncomeTypeMother::create();
        $income = IncomeMother::create();

        $incomeType->addIncome($income);
        $incomeType->addIncome($income); // Add again

        $this->assertCount(1, $incomeType->incomes());
    }

    public function testItShouldRemoveAnIncome(): void
    {
        $incomeType = IncomeTypeMother::create();
        $income = IncomeMother::create();

        $incomeType->addIncome($income);
        $this->assertCount(1, $incomeType->incomes());

        $incomeType->removeIncome($income);

        $this->assertCount(0, $incomeType->incomes());
    }
}
