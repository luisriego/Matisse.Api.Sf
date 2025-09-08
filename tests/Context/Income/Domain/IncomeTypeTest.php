<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Domain;

use App\Context\Income\Domain\IncomeType;
use PHPUnit\Framework\TestCase;

class IncomeTypeTest extends TestCase
{
    public function test_it_should_create_an_income_type(): void
    {
        $incomeType = IncomeTypeMother::create();

        $this->assertInstanceOf(IncomeType::class, $incomeType);
        $this->assertNotEmpty($incomeType->id());
        $this->assertNotEmpty($incomeType->name());
        $this->assertNotEmpty($incomeType->code());
        $this->assertNotEmpty($incomeType->description());
        $this->assertEmpty($incomeType->incomes());
    }

    public function test_it_should_add_an_income(): void
    {
        $incomeType = IncomeTypeMother::create();
        $income = IncomeMother::create();

        $incomeType->addIncome($income);

        $this->assertCount(1, $incomeType->incomes());
        $this->assertTrue($incomeType->incomes()->contains($income));
    }

    public function test_it_should_not_add_duplicate_income(): void
    {
        $incomeType = IncomeTypeMother::create();
        $income = IncomeMother::create();

        $incomeType->addIncome($income);
        $incomeType->addIncome($income); // Add again

        $this->assertCount(1, $incomeType->incomes());
    }

    public function test_it_should_remove_an_income(): void
    {
        $incomeType = IncomeTypeMother::create();
        $income = IncomeMother::create();

        $incomeType->addIncome($income);
        $this->assertCount(1, $incomeType->incomes());

        $incomeType->removeIncome($income);

        $this->assertCount(0, $incomeType->incomes());
    }
}
