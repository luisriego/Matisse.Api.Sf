<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

class UpdateRecurrentExpenseRequestDto implements RequestDto
{
    public readonly ?int $amount;
    public readonly ?string $type;
    public readonly ?int $dueDay;
    public readonly ?array $monthsOfYear;
    public readonly ?string $startDate;
    public readonly ?string $endDate;
    public readonly ?string $description;
    public readonly ?string $notes;

    public function __construct(Request $request)
    {
        $this->amount = $request->get('amount');
        $this->type = $request->get('type');
        $this->dueDay = $request->get('dueDay');
        $this->monthsOfYear = $request->get('monthsOfYear');
        $this->startDate = $request->get('startDate');
        $this->endDate = $request->get('endDate');
        $this->description = $request->get('description');
        $this->notes = $request->get('notes');
    }
}
