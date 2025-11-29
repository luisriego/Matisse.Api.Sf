<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\AssignAccountTypeToExpenseType;

use App\Context\Account\Domain\AccountRepository;
use App\Context\Expense\Application\UseCase\AssignAccountTypeToExpenseType\AssignAccountTypeToExpenseTypeCommand;
use App\Context\Expense\Application\UseCase\AssignAccountTypeToExpenseType\AssignAccountTypeToExpenseTypeCommandHandler;
use App\Context\Expense\Domain\ExpenseTypeRepository;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AssignAccountTypeToExpenseTypeCommandHandlerTest extends TestCase
{
    private ExpenseTypeRepository&MockObject $expenseTypeRepository;
    private AccountRepository&MockObject $accountRepository;
    private AssignAccountTypeToExpenseTypeCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->expenseTypeRepository = $this->createMock(ExpenseTypeRepository::class);
        $this->accountRepository = $this->createMock(AccountRepository::class);

        $this->handler = new AssignAccountTypeToExpenseTypeCommandHandler(
            $this->expenseTypeRepository,
            $this->accountRepository
        );
    }

    public function test_it_should_assign_account_to_expense_type(): void
    {
        $command = new AssignAccountTypeToExpenseTypeCommand(
            Uuid::random()->value(),
            Uuid::random()->value()
        );

        $expenseType = ExpenseTypeMother::create(id: $command->getExpenseTypeId());
        $account = AccountMother::create();

        $this->expenseTypeRepository
            ->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($command->getExpenseTypeId())
            ->willReturn($expenseType);

        $this->accountRepository
            ->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($command->getAccountTypeId())
            ->willReturn($account);

        $this->expenseTypeRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($savedExpenseType) use ($account) {
                return $savedExpenseType->account() === $account;
            }));

        ($this->handler)($command);
    }

    public function test_it_should_throw_exception_when_expense_type_not_found(): void
    {
        $command = new AssignAccountTypeToExpenseTypeCommand(
            Uuid::random()->value(),
            Uuid::random()->value()
        );

        $this->expenseTypeRepository
            ->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($command->getExpenseTypeId())
            ->willThrowException(new ResourceNotFoundException());

        $this->expectException(ResourceNotFoundException::class);

        ($this->handler)($command);
    }

    public function test_it_should_throw_exception_when_account_not_found(): void
    {
        $command = new AssignAccountTypeToExpenseTypeCommand(
            Uuid::random()->value(),
            Uuid::random()->value()
        );

        $expenseType = ExpenseTypeMother::create(id: $command->getExpenseTypeId());

        $this->expenseTypeRepository
            ->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($command->getExpenseTypeId())
            ->willReturn($expenseType);

        $this->accountRepository
            ->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($command->getAccountTypeId())
            ->willThrowException(new ResourceNotFoundException());

        $this->expectException(ResourceNotFoundException::class);

        ($this->handler)($command);
    }
}
