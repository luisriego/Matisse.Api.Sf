<?php

declare(strict_types=1);

namespace App\Context\Income\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

class UpdateIncomeRequestDto implements RequestDto
{
    public string $id;
    public ?string $dueDate;
    public ?string $description;

    public function __construct(Request $request)
    {
        $this->dueDate = $request->get('dueDate');
        $this->description = $request->get('description');
    }
}
