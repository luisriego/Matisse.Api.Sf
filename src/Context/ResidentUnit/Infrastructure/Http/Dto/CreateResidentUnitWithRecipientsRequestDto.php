<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

readonly class CreateResidentUnitWithRecipientsRequestDto implements RequestDto
{
    public string $id;
    public string $unit;
    public float $idealFraction;
    public array $notificationRecipients;

    public function __construct(Request $request)
    {
        $data = $request->toArray();
        $this->id = $data['id'];
        $this->unit = $data['unit'];
        $this->idealFraction = $data['idealFraction'];
        $this->notificationRecipients = $data['notificationRecipients'] ?? [];
    }
}
