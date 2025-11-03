<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Domain;

use App\Context\Gas\Domain\Bus\GasPriceWasDefined;
use App\Context\Gas\Domain\Bus\GasReadingWasRecorded;
use App\Context\Gas\Domain\Gas;
use App\Context\Gas\Domain\ValueObject\BufferPercentage;
use App\Context\Gas\Domain\ValueObject\CylinderCapacity;
use App\Context\Gas\Domain\ValueObject\GasAmount;
use App\Context\Gas\Domain\ValueObject\GasId;
use App\Context\Gas\Domain\ValueObject\ReadingInM3;
use App\Context\ResidentUnit\Domain\ResidentUnitId;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\ValueObject\Month;
use App\Shared\Domain\ValueObject\Year;
use App\Tests\Context\Gas\Domain\ValueObject\BufferPercentageMother;
use App\Tests\Context\Gas\Domain\ValueObject\CylinderCapacityMother;
use App\Tests\Context\Gas\Domain\ValueObject\GasAmountMother;
use App\Tests\Context\Gas\Domain\ValueObject\GasIdMother;
use App\Tests\Context\Gas\Domain\ValueObject\ReadingInM3Mother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitIdMother;
use App\Tests\Shared\Domain\ValueObject\MonthMother;
use App\Tests\Shared\Domain\ValueObject\YearMother;
use DateMalformedStringException;
use PHPUnit\Framework\TestCase;

class GasTest extends TestCase
{
    /**
     * @throws DateMalformedStringException
     */
    public function test_it_should_record_a_gas_reading(): void
    {
        $id = GasIdMother::create();
        $residentUnitId = ResidentUnitIdMother::create();
        $year = YearMother::create();
        $month = MonthMother::create();
        $reading = ReadingInM3Mother::create();

        $gas = Gas::recordReading(
            $id,
            $residentUnitId,
            $year,
            $month,
            $reading
        );

        $this->assertInstanceOf(Gas::class, $gas);
        $this->assertSame($id, $gas->id());

        $events = $gas->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(GasReadingWasRecorded::class, $events[0]);
        $this->assertSame($id->value(), $events[0]->aggregateId());
        $this->assertSame($residentUnitId->value(), $events[0]->residentUnitId);
        $this->assertSame($year->value(), $events[0]->year);
        $this->assertSame($month->value(), $events[0]->month);
        $this->assertSame($reading->value(), $events[0]->reading);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function test_it_should_define_gas_price(): void
    {
        $amount = GasAmountMother::create(); // Default 10000 (int)
        $capacity = CylinderCapacityMother::create(); // Default 100 (int)
        $buffer = BufferPercentageMother::create(); // Default 10 (int)

        $gas = Gas::definePrice(
            $amount,
            $capacity,
            $buffer
        );

        $this->assertInstanceOf(Gas::class, $gas);
        $this->assertNotNull($gas->id());
        $this->assertIsFloat($gas->pricePerM3());

        // Recalculate expected price based on default values and conversions
        $expectedPricePerM3 = ($amount->toFloat() / $capacity->toM3()) * (1 + $buffer->toFactor());
        $this->assertEquals($expectedPricePerM3, $gas->pricePerM3());

        $events = $gas->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(GasPriceWasDefined::class, $events[0]);
        $this->assertSame($gas->id()->value(), $events[0]->aggregateId());
        $this->assertEquals($expectedPricePerM3, $events[0]->pricePerM3);
    }

    public function test_it_should_throw_exception_for_invalid_reading_in_record_reading(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GasMother::createForRecordReading(reading: ReadingInM3Mother::create(-100.0));
    }

    public function test_it_should_throw_exception_for_invalid_month_in_record_reading(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GasMother::createForRecordReading(month: MonthMother::create(13));
    }

    public function test_it_should_throw_exception_for_invalid_year_in_record_reading(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GasMother::createForRecordReading(year: YearMother::create(1900));
    }

    public function test_it_should_throw_exception_for_invalid_amount_in_define_price(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GasMother::createForDefinePrice(amount: GasAmountMother::create(-10)); // Changed from -10.0 to -10
    }

    public function test_it_should_throw_exception_for_invalid_capacity_in_define_price(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GasMother::createForDefinePrice(capacity: CylinderCapacityMother::create(-10)); // Changed from -10.0 to -10
    }

    public function test_it_should_throw_exception_for_invalid_buffer_in_define_price(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GasMother::createForDefinePrice(buffer: BufferPercentageMother::create(-1)); // Changed from -0.1 to -1
    }
}
