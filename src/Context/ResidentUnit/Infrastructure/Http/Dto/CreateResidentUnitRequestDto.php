<?php

namespace App\Context\ResidentUnit\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

class CreateResidentUnitRequestDto implements RequestDto
{
    public string $id;
    public  string $unit;
    public float $idealFraction;

    public function __construct(Request $request)
    {
        $this->id = $request->get('id');
        $this->unit = $request->get('unit');
        $this->idealFraction = $request->get('idealFraction');
    }
}