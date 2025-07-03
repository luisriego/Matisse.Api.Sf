<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\ExpenseAmount;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\ExpenseTypeCode;
use App\Context\Expense\Domain\ExpenseTypeDescription;
use App\Context\Expense\Domain\ExpenseTypeDistributionMethod;
use App\Context\Expense\Domain\ExpenseTypeId;
use App\Context\Expense\Domain\ExpenseTypeName;

final class ExpenseTypeMother
{
    public static function create(
        ?ExpenseTypeId $id = null,
        ?ExpenseTypeCode $code = null,
        ?ExpenseTypeName $name = null,
        ?ExpenseTypeDistributionMethod $distributionMethod = null,
        ?ExpenseTypeDescription $description = null
    ): ExpenseType
    {
        return ExpenseType::create(
            $id ?? ExpenseTypeIdMother::create(),
            $code ?? ExpenseTypeCodeMother::create(),
            $name ?? ExpenseTypeNameMother::create(),
            $distributionMethod ?? ExpenseTypeDistributionMethodMother::create(),
            $description ?? ExpenseTypeDescriptionMother::create()
        );
    }
}