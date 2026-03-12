<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

class CreateRecurringExpenseRequestDto implements RequestDto
{
    public readonly string $id;
    public readonly int $amount;
    public readonly string $type;
    public readonly string $accountId;
    public readonly int $dueDay;
    public readonly array $monthsOfYear;
    public readonly string $startDate;
    public readonly string $endDate;
    public readonly string $description;
    public readonly string $notes;
    public readonly bool $hasPredefinedAmount;

    public function __construct(Request $request)
    {
        $this->id = $request->get('id');
        $this->amount = $request->get('amount');
        $this->type = $request->get('type');
        $this->accountId = $request->get('accountId');
        $this->dueDay = $request->get('dueDay');
        $this->monthsOfYear = $request->get('monthsOfYear');
        $this->startDate = $request->get('startDate', '');
        $this->endDate = $request->get('endDate', '');
        $this->description = $request->get('description', '');
        $this->notes = $request->get('notes', '');
        $this->hasPredefinedAmount = $request->get('hasPredefinedAmount', true);
    }
}
