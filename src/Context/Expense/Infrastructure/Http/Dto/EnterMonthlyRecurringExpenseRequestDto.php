<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Dto;

use App\Context\Account\Domain\AccountId;
use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDate;
use App\Context\Expense\Domain\ValueObject\ExpenseId; // Assuming ExpenseId is used for the new expense ID
use App\Context\Expense\Domain\ValueObject\RecurringExpenseId; // Assuming this VO exists or will be created
use App\Shared\Domain\Exception\InvalidDataException;
use App\Shared\Infrastructure\RequestDto;
use DateTimeImmutable;
use Exception;
use Symfony\Component\HttpFoundation\Request;

use function json_decode;

final class EnterMonthlyRecurringExpenseRequestDto implements RequestDto
{
    public ExpenseId $id;
    public RecurringExpenseId $recurringExpenseId;
    public AccountId $accountId;
    public ExpenseAmount $amount;
    public ExpenseDueDate $date;

    /**
     * @throws Exception
     */
    public function __construct(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['id']) || !isset($data['recurringExpenseId']) || !isset($data['accountId']) || !isset($data['amount']) || !isset($data['date'])) {
            throw new InvalidDataException('Os campos "id", "recurringExpenseId", "accountId", "amount" e "date" são obrigatórios.');
        }

        $this->id = new ExpenseId((string) $data['id']);
        $this->recurringExpenseId = new RecurringExpenseId((string) $data['recurringExpenseId']);
        $this->accountId = new AccountId((string) $data['accountId']);
        $this->amount = new ExpenseAmount((int) $data['amount']);
        $this->date = new ExpenseDueDate(new DateTimeImmutable((string) $data['date']));
    }
}
