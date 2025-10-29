<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Application\UseCase\FindResidentUnitById;

use App\Context\ResidentUnit\Application\Response\ResidentUnitResponseConverter;
use App\Context\ResidentUnit\Application\UseCase\FindResidentUnitById\FindResidentUnitByIdQuery;
use App\Context\ResidentUnit\Application\UseCase\FindResidentUnitById\FindResidentUnitByIdQueryHandler;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitIdMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class FindResidentUnitByIdQueryHandlerTest extends TestCase
{
    private ResidentUnitRepository|MockInterface $repository;
    private FindResidentUnitByIdQueryHandler $handler;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(ResidentUnitRepository::class);
        // Use a real instance of the converter, not a mock.
        $converter = new ResidentUnitResponseConverter();
        $this->handler = new FindResidentUnitByIdQueryHandler($this->repository, $converter);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_it_should_find_a_resident_unit(): void
    {
        // Arrange
        $residentUnit = ResidentUnitMother::create();

        $this->repository
            ->shouldReceive('findOneByIdOrFail')
            ->with($residentUnit->id())
            ->once()
            ->andReturn($residentUnit);

        $query = new FindResidentUnitByIdQuery($residentUnit->id());

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertEquals($residentUnit->id(), $result->id);
        $this->assertEquals($residentUnit->unit(), $result->unit);
        $this->assertEquals($residentUnit->idealFraction(), $result->idealFraction);
    }

    public function test_it_should_throw_exception_when_not_found(): void
    {
        // Arrange
        $residentUnitId = ResidentUnitIdMother::create()->value();

        $this->repository
            ->shouldReceive('findOneByIdOrFail')
            ->with($residentUnitId)
            ->once()
            ->andThrow(new ResourceNotFoundException());

        $query = new FindResidentUnitByIdQuery($residentUnitId);

        // Assert & Act
        $this->expectException(ResourceNotFoundException::class);
        ($this->handler)($query);
    }
}
