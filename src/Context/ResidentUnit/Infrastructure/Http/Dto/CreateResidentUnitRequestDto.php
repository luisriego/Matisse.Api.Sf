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
    public array $notificationRecipients;

    public function __construct(Request $request)
    {
        $this->id = $request->get('id');
        $this->unit = $request->get('unit');
        $this->idealFraction = $request->get('idealFraction');
        $this->notificationRecipients = $request->get('notificationRecipients', []);
    }
}
