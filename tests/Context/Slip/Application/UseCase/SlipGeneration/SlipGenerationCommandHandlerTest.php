<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Application\UseCase\SlipGeneration;

use App\Context\Condominium\Domain\CondominiumConfiguration;
use App\Context\Condominium\Domain\Service\CondominiumFundAmountService; // ¡Añade esta línea!
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeRepository;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\Slip\Application\UseCase\SlipGeneration\SlipGenerationCommand;
use App\Context\Slip\Application\UseCase\SlipGeneration\SlipGenerationCommandHandler;
use App\Context\Slip\Domain\Service\MonthlyExpenseAggregatorService;
use App\Context\Slip\Domain\Service\SlipAmountCalculatorService;
use App\Context\Slip\Domain\Service\SlipFactory;
use App\Context\Slip\Domain\Service\SlipGenerationPolicy;
use App\Context\Slip\Domain\SlipRepository;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SlipGenerationCommandHandlerTest extends TestCase
{
    private MockObject|SlipRepository $slipRepository;
    private MockObject|ExpenseRepository $expenseRepository;
    private MockObject|RecurringExpenseRepository $recurringExpenseRepository;
    private MockObject|ResidentUnitRepository $residentUnitRepository;
    private MockObject|SlipGenerationPolicy $generationPolicy;
    private MockObject|MonthlyExpenseAggregatorService $monthlyExpenseAggregatorService;
    private MockObject|SlipAmountCalculatorService $slipAmountCalculatorService;
    private MockObject|StoredEventRepository $storedEventRepository;
    private MockObject|LoggerInterface $logger;
    private MockObject|ExpenseTypeRepository $expenseTypeRepository;
    private MockObject|CondominiumFundAmountService $condominiumFundAmountService; // ¡Añade esta propiedad!

    private SlipFactory $slipFactory;
    private SlipGenerationCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->slipRepository = $this->createMock(SlipRepository::class);
        $this->expenseRepository = $this->createMock(ExpenseRepository::class);
        $this->recurringExpenseRepository = $this->createMock(RecurringExpenseRepository::class);
        $this->residentUnitRepository = $this->createMock(ResidentUnitRepository::class);
        $this->generationPolicy = $this->createMock(SlipGenerationPolicy::class);

        // Inicializa los mocks para las dependencias de SlipFactory
        $this->monthlyExpenseAggregatorService = $this->createMock(MonthlyExpenseAggregatorService::class);
        $this->slipAmountCalculatorService = $this->createMock(SlipAmountCalculatorService::class);
        $this->storedEventRepository = $this->createMock(StoredEventRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->expenseTypeRepository = $this->createMock(ExpenseTypeRepository::class);
        $this->condominiumFundAmountService = $this->createMock(CondominiumFundAmountService::class); // ¡Inicializa el mock!

        // Configura el mock de condominiumFundAmountService
        $condominiumConfig = $this->createConfiguredMock(CondominiumConfiguration::class, [
            'reserveFundAmount' => 1000, // Monto por defecto para el test
            'constructionFundAmount' => 500, // Monto por defecto para el test
        ]);
        $this->condominiumFundAmountService->method('getActiveConfigurationForDate')->willReturn($condominiumConfig);


        // Instancia SlipFactory con los mocks correctos (6 argumentos)
        $this->slipFactory = new SlipFactory(
            $this->monthlyExpenseAggregatorService,
            $this->slipAmountCalculatorService,
            $this->storedEventRepository,
            $this->logger,
            $this->expenseTypeRepository,
            $this->condominiumFundAmountService // ¡Este es el sexto argumento!
        );

        // Instancia el handler pasando la SlipFactory correctamente configurada
        $this->handler = new SlipGenerationCommandHandler(
            $this->slipRepository,
            $this->expenseRepository,
            $this->recurringExpenseRepository,
            $this->residentUnitRepository,
            $this->generationPolicy,
            $this->slipFactory
        );
    }

    public function testInvokeHappyPath(): void
    {
        // Arrange
        $command = new SlipGenerationCommand(2024, 5);

        $this->generationPolicy->expects($this->once())->method('check');
        $this->slipRepository->expects($this->once())->method('deleteByDateRange');

        $typeEqual = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'EQUAL']);
        $expenses = [$this->createConfiguredMock(Expense::class, ['amount' => 10000, 'type' => $typeEqual])];
        $this->expenseRepository->method('findActiveByDateRange')->willReturn($expenses);

        $resident = $this->createConfiguredMock(ResidentUnit::class, ['id' => 'resident-1', 'idealFraction' => 0.0]);
        $this->residentUnitRepository->method('findAllActive')->willReturn([$resident]);

        // Configura el mock de storedEventRepository para que no devuelva eventos de gas
        $this->storedEventRepository->method('findByEventNamesAndOccurredBetween')->willReturn([]);

        // Configura el mock de monthlyExpenseAggregatorService para que devuelva los totales esperados
        $this->monthlyExpenseAggregatorService->method('aggregateTotals')->willReturn([
            'equal' => 10000,
            'fraction' => 0,
            'individual' => 0,
        ]);

        // Configura el mock de slipAmountCalculatorService para que devuelva el monto calculado
        // Asumimos que el cálculo base es 10000, y luego se le sumarán los fondos (1000 + 500 = 1500)
        // Por lo tanto, el total esperado en el slip será 10000 + 1500 = 11500
        $this->slipAmountCalculatorService->method('calculate')->willReturn(10000);

        // Configura el mock de expenseTypeRepository para el método findOneByCodeOrFail
        $gasExpenseType = $this->createConfiguredMock(ExpenseType::class, ['id' => 'gas-expense-type-id']);
        $this->expenseTypeRepository->method('findOneByCodeOrFail')->willReturn($gasExpenseType);


        $this->slipRepository->expects($this->once())->method('save');
        $this->slipRepository->expects($this->once())->method('flush');

        // Act
        ($this->handler)($command);
    }

    public function testInvokeStopsWhenPolicyFails(): void
    {
        // Arrange
        $command = new SlipGenerationCommand(2024, 1);

        $this->generationPolicy->expects($this->once())
            ->method('check')
            ->willThrowException(new \RuntimeException('Generation not allowed.'));

        $this->slipRepository->expects($this->never())->method('deleteByDateRange');
        $this->slipRepository->expects($this->never())->method('save');
        $this->slipRepository->expects($this->never())->method('flush');

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        ($this->handler)($command);
    }

    public function testInvokeWithNoExpensesDoesNotSaveSlips(): void
    {
        // Arrange
        $command = new SlipGenerationCommand(2024, 5);

        $this->expenseRepository->method('findActiveByDateRange')->willReturn([]);
        $this->recurringExpenseRepository->method('findActiveForDateRange')->willReturn([]);
        $this->residentUnitRepository->method('findAllActive')->willReturn([$this->createMock(ResidentUnit::class)]);

        // Configura el mock de storedEventRepository para que no devuelva eventos de gas
        $this->storedEventRepository->method('findByEventNamesAndOccurredBetween')->willReturn([]);

        // Configura el mock de monthlyExpenseAggregatorService para que devuelva cero
        $this->monthlyExpenseAggregatorService->method('aggregateTotals')->willReturn([
            'equal' => 0,
            'fraction' => 0,
            'individual' => 0,
        ]);

        // Configura el mock de expenseTypeRepository para el método findOneByCodeOrFail
        $gasExpenseType = $this->createConfiguredMock(ExpenseType::class, ['id' => 'gas-expense-type-id']);
        $this->expenseTypeRepository->method('findOneByCodeOrFail')->willReturn($gasExpenseType);

        $this->slipRepository->expects($this->never())->method('save');
        $this->slipRepository->expects($this->never())->method('flush');

        // Act
        ($this->handler)($command);
    }

    public function testInvokeWithNoResidentsDoesNotSaveSlips(): void
    {
        // Arrange
        $command = new SlipGenerationCommand(2024, 5);

        $typeEqual = $this->createConfiguredMock(ExpenseType::class, ['distributionMethod' => 'EQUAL']);
        $expenses = [$this->createConfiguredMock(Expense::class, ['amount' => 10000, 'type' => $typeEqual])];
        $this->expenseRepository->method('findActiveByDateRange')->willReturn($expenses);
        $this->residentUnitRepository->method('findAllActive')->willReturn([]);

        // Configura el mock de storedEventRepository para que no devuelva eventos de gas
        $this->storedEventRepository->method('findByEventNamesAndOccurredBetween')->willReturn([]);

        // Configura el mock de monthlyExpenseAggregatorService para que devuelva los totales esperados
        $this->monthlyExpenseAggregatorService->method('aggregateTotals')->willReturn([
            'equal' => 10000,
            'fraction' => 0,
            'individual' => 0,
        ]);

        // Configura el mock de expenseTypeRepository para el método findOneByCodeOrFail
        $gasExpenseType = $this->createConfiguredMock(ExpenseType::class, ['id' => 'gas-expense-type-id']);
        $this->expenseTypeRepository->method('findOneByCodeOrFail')->willReturn($gasExpenseType);

        $this->slipRepository->expects($this->never())->method('save');
        $this->slipRepository->expects($this->never())->method('flush');

        // Act
        ($this->handler)($command);
    }
}