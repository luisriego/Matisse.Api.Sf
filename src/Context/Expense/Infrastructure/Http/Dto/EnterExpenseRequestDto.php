<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

class EnterExpenseRequestDto implements RequestDto
{
    public readonly string $id;
    public readonly int $amount;
    public readonly string $type;
    public readonly string $accountId;
    public readonly string $dueDate;
    public readonly ?bool $isActive;
    public readonly ?string $description;
    public readonly ?string $residentUnitId;

    public function __construct(Request $request)
    {
        $this->id = $request->get('id');
        $this->amount = $request->get('amount');
        $this->type = $request->get('type');
        $this->accountId = $request->get('accountId');
        $this->dueDate = $request->get('dueDate');
        $this->isActive = $request->get('isActive');
        $this->description = $request->get('description');
        $this->residentUnitId = $request->get('residentUnitId');
    }
}
