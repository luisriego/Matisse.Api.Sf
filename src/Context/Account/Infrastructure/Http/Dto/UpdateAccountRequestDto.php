<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

final readonly class UpdateAccountRequestDto implements RequestDto
{
    public string $code;
    public string $name;

    public function __construct(Request $request)
    {
        $this->code = $request->get('code');
        $this->name = $request->get('name');
    }
}
