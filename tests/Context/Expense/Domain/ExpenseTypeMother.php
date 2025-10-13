<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeCode;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeDescription;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeDistributionMethod;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeId;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeName;

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