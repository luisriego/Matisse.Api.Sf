<?php

declare(strict_types=1);

namespace App\Context\Income\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

class EnterIncomeRequestDto implements RequestDto
{
    public string $id;
    public string $residentUnitId;
    public int $amount;
    public string $type;
    public string $dueDate;
    public ?bool $isActive;
    public ?string $description;

    public function __construct(Request $request)
    {
        $this->id = $request->get('id');
        $this->amount = $request->get('amount');
        $this->residentUnitId = $request->get('residentUnitId');
        $this->type = $request->get('type');
        $this->dueDate = $request->get('dueDate');
        $this->isActive = $request->get('isActive');
        $this->description = $request->get('description');
    }
}
