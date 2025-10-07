<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

use function json_decode;

final readonly class CreateAccountRequestDto implements RequestDto
{
    public ?string $id;
    public ?string $code;
    public ?string $name;
    public ?string $description;

    public function __construct(Request $request)
    {
        $content = json_decode($request->getContent(), true);

        $this->id = $content['id'] ?? null;
        $this->code = $content['code'] ?? null;
        $this->name = $content['name'] ?? null;
        $this->description = $content['description'] ?? null;
    }
}
