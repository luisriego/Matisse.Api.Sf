<?php

declare(strict_types=1);

namespace App\Context\Income\Infrastructure\Http\Dto;

use App\Context\Income\Application\UseCase\EnterIncome\EnterIncomeCommand;
use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

class EnterIncomeRequestDto implements RequestDto
{
    public string $id;
    public string $residentUnitId;
    public int $amount;
    public string $type;
    public string $dueDate;
    public ?string $description;

    public function __construct(Request $request)
    {
        $this->id = $request->get('id');
        $this->amount = $request->get('amount');
        $this->residentUnitId = $request->get('residentUnitId');
        $this->type = $request->get('type');
        $this->dueDate = $request->get('dueDate');
        $this->description = $request->get('description');
    }

    public function toCommand(): EnterIncomeCommand
    {
        return new EnterIncomeCommand(
            $this->id,
            $this->amount,
            $this->residentUnitId,
            $this->type,
            $this->dueDate,
            $this->description,
        );
    }
}
