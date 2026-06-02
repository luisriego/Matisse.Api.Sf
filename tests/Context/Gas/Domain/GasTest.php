<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Domain;

use App\Context\Gas\Domain\Event\GasPriceWasDefined;
use App\Context\Gas\Domain\Event\GasReadingWasRecorded;
use App\Context\Gas\Domain\Gas;
use App\Shared\Domain\Exception\InvalidArgumentException;
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

use function round;

class GasTest extends TestCase
{
    /**
     * @throws DateMalformedStringException
     */
    public function testItShouldRecordAGasReading(): void
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
            $reading,
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
    public function testItShouldDefineGasPrice(): void
    {
        $amount = GasAmountMother::create(); // Default 10000 (int)
        $capacity = CylinderCapacityMother::create(); // Default 100 (int)
        $buffer = BufferPercentageMother::create(); // Default 10 (int)

        $gas = Gas::definePrice(
            $amount,
            $capacity,
            $buffer,
        );

        $this->assertInstanceOf(Gas::class, $gas);
        $this->assertNotNull($gas->id());
        $this->assertIsInt($gas->pricePerM3InCents());

        $billCents = $amount->value();
        $kg = $capacity->value();
        $bufferPct = $buffer->value();
        $expectedPricePerM3InCents = (int) round((2 * $billCents * (100 + $bufferPct)) / ($kg * 100));

        $this->assertSame($expectedPricePerM3InCents, $gas->pricePerM3InCents());

        $events = $gas->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(GasPriceWasDefined::class, $events[0]);
        $this->assertSame($gas->id()->value(), $events[0]->aggregateId());
        $this->assertSame($expectedPricePerM3InCents, $events[0]->pricePerM3InCents);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testItShouldSetDirectGasPrice(): void
    {
        $pricePerM3InCents = 2600;

        $gas = Gas::setDirectPrice($pricePerM3InCents);

        $this->assertSame($pricePerM3InCents, $gas->pricePerM3InCents());

        $events = $gas->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(GasPriceWasDefined::class, $events[0]);
        $this->assertSame($gas->id()->value(), $events[0]->aggregateId());
        $this->assertSame($pricePerM3InCents, $events[0]->pricePerM3InCents);
    }

    public function testItShouldThrowExceptionForInvalidReadingInRecordReading(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GasMother::createForRecordReading(reading: ReadingInM3Mother::create(-100.0));
    }

    public function testItShouldThrowExceptionForInvalidMonthInRecordReading(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GasMother::createForRecordReading(month: MonthMother::create(13));
    }

    public function testItShouldThrowExceptionForInvalidYearInRecordReading(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GasMother::createForRecordReading(year: YearMother::create(1900));
    }

    public function testItShouldThrowExceptionForInvalidAmountInDefinePrice(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GasMother::createForDefinePrice(amount: GasAmountMother::create(-10)); // Changed from -10.0 to -10
    }

    public function testItShouldThrowExceptionForInvalidCapacityInDefinePrice(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GasMother::createForDefinePrice(capacity: CylinderCapacityMother::create(-10)); // Changed from -10.0 to -10
    }

    public function testItShouldThrowExceptionForInvalidBufferInDefinePrice(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GasMother::createForDefinePrice(buffer: BufferPercentageMother::create(-1)); // Changed from -0.1 to -1
    }
}
