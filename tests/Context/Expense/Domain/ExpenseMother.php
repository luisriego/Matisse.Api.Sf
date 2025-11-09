<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Account\Domain\Account;
use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDate;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Tests\Context\Account\Domain\AccountMother;
use DateTime;

final class ExpenseMother
{
    public static function create(
        ?string $id = null,
        ?int $amount = null,
        ?ExpenseType $type = null,
        ?Account $account = null,
        ?DateTime $dueDate = null,
        ?bool $isActive = true,
        ?string $description = 'Default Description',
        ?string $residentUnitId = null,
        bool $forceAccount = true // Flag to control account creation
    ): Expense {
        $finalAccount = $forceAccount ? ($account ?? AccountMother::create()) : $account;

        return new Expense(
            $id ?? ExpenseIdMother::create()->value(),
            $amount ?? ExpenseAmountMother::create()->value(),
            $type ?? ExpenseTypeMother::create(),
            $finalAccount,
            $dueDate ?? ExpenseDueDateMother::create()->toDateTime(),
            $isActive,
            $description,
            $residentUnitId
        );
    }

    public static function createFromValueObjects(
        ExpenseId $id,
        ExpenseAmount $amount,
        ?Account $account,
        ExpenseDueDate $dueDate,
        ?ExpenseType $type = null,
        ?bool $isActive = true,
        ?string $description = 'Default Description',
        ?string $residentUnitId = null
    ): Expense {
        return new Expense(
            $id->value(),
            $amount->value(),
            $type ?? ExpenseTypeMother::create(),
            $account,
            $dueDate->toDateTime(),
            $isActive,
            $description,
            $residentUnitId
        );
    }

    public static function createInactive(
        ?string $id = null,
        ?int $amount = null,
        ?ExpenseType $type = null,
        ?Account $account = null,
        ?DateTime $dueDate = null,
        ?string $description = 'Default Description',
        ?string $residentUnitId = null
    ): Expense {
        return self::create($id, $amount, $type, $account, $dueDate, false, $description, $residentUnitId);
    }

    public static function createWithNoAccount(
        ?string $id = null,
        ?int $amount = null,
        ?ExpenseType $type = null,
        ?DateTime $dueDate = null,
        ?bool $isActive = true,
        ?string $description = 'Default Description',
        ?string $residentUnitId = null
    ): Expense {
        // Call create but force the account to be null
        return self::create($id, $amount, $type, null, $dueDate, $isActive, $description, $residentUnitId, false);
    }
}
