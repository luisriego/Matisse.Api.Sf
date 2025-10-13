<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

class CompensateExpenseRequestDto implements RequestDto
{
    public ?int $amount;

    public function __construct(Request $request)
    {
        $this->amount = $request->get('amount');
    }
}
