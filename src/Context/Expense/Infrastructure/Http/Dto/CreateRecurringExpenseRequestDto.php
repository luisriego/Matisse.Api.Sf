<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

class CreateRecurringExpenseRequestDto implements RequestDto
{
    public string $id;
    public int $amount;
    public string $type;
    public int $dueDay;
    public array $monthsOfYear;
    public string $startDate;
    public string $endDate;
    public string $description;
    public string $notes;

    public function __construct(Request $request)
    {
        $this->id = $request->get('id');
        $this->amount = $request->get('amount');
        $this->type = $request->get('type');
        $this->dueDay = $request->get('dueDay');
        $this->monthsOfYear = $request->get('monthsOfYear');
        $this->startDate = $request->get('startDate', '');
        $this->endDate = $request->get('endDate', '');
        $this->description = $request->get('description', '');
        $this->notes = $request->get('notes', '');
    }
}
