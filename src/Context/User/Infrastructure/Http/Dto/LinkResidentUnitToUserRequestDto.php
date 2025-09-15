<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

final class LinkResidentUnitToUserRequestDto implements RequestDto
{
    public ?string $residentUnitId;

    public function __construct(Request $request)
    {
        $this->residentUnitId = $request->get('residentUnitId');
    }
}