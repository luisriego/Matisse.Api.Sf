<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\FindTypes;

use App\Context\Expense\Application\UseCase\FindTypes\FindTypesQuery;
use App\Context\Expense\Application\UseCase\FindTypes\FindTypesQueryHandler;
use App\Context\Expense\Domain\ExpenseTypeRepository;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class FindTypesQueryHandlerTest extends TestCase
{
    private FindTypesQueryHandler $handler;
    private ExpenseTypeRepository|MockInterface $repository;
    private SerializerInterface|MockInterface $serializer;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(ExpenseTypeRepository::class);
        $this->serializer = Mockery::mock(SerializerInterface::class);
        $this->handler = new FindTypesQueryHandler($this->repository, $this->serializer);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_it_should_find_all_expense_types(): void
    {
        // Arrange
        $type1 = ExpenseTypeMother::create();
        $type2 = ExpenseTypeMother::create();
        $types = [$type1, $type2];
        $typesArray = [
            ['id' => $type1->id()],
            ['id' => $type2->id()],
        ];

        $this->repository->shouldReceive('findAll')
            ->once()
            ->andReturn($types);

        $this->serializer->shouldReceive('normalize')
            ->once()
            ->with($types)
            ->andReturn($typesArray);

        $query = new FindTypesQuery();

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertEquals($typesArray, $result);
    }

    public function test_it_should_return_empty_array_if_no_types_found(): void
    {
        // Arrange
        $this->repository->shouldReceive('findAll')
            ->once()
            ->andReturn([]);

        $this->serializer->shouldReceive('normalize')
            ->once()
            ->with([])
            ->andReturn([]);

        $query = new FindTypesQuery();

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
