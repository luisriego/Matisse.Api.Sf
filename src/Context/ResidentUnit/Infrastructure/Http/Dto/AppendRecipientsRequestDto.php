<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

readonly class AppendRecipientsRequestDto implements RequestDto
{
    public string $id;
    public string $name;
    public string $email;

    public function __construct(Request $request)
    {
        $this->id = $request->attributes->get('id');
        $this->name = $request->get('name');
        $this->email = $request->get('email');
    }
}
