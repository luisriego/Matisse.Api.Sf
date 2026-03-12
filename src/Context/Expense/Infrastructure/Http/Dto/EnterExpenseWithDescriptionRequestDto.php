<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

class EnterExpenseWithDescriptionRequestDto implements RequestDto
{
    public readonly string $id;
    public readonly int $amount;
    public readonly string $type;
    public readonly string $accountId;
    public readonly string $dueDate;
    public readonly string $description;

    public function __construct(Request $request)
    {
        $this->id = $request->get('id');
        $this->amount = $request->get('amount');
        $this->type = $request->get('type');
        $this->accountId = $request->get('accountId');
        $this->dueDate = $request->get('dueDate');
        $this->description = $request->get('description');
    }
}
