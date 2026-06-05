<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

readonly class CreateResidentUnitRequestDto implements RequestDto
{
    public string $id;
    public string $unit;
    public float $idealFraction;
    public string $email;
    public ?string $name;

    public function __construct(Request $request)
    {
        $data = $request->toArray();
        $this->id = $data['id'];
        $this->unit = $data['unit'];
        $this->idealFraction = $data['idealFraction'];
        $this->email = $data['email'];
        $this->name = $data['name'] ?? null;
    }
}
