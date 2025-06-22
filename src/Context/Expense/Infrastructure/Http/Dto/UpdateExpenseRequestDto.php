<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

class UpdateExpenseRequestDto implements RequestDto
{
    public string $id;
    public ?int $amount;
    public ?string $dueDate;
    public ?string $description;

    public function __construct(Request $request)
    {
        $this->amount = $request->get('amount');
        $this->dueDate = $request->get('dueDate');
        $this->description = $request->get('description');
    }
}
