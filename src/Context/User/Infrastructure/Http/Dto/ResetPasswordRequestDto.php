<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use function json_decode;
use function is_string;

use const JSON_THROW_ON_ERROR;

final class ResetPasswordRequestDto implements RequestDto
{
    private string $newPassword;

    public function __construct(Request $request)
    {
        try {
            $data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new BadRequestHttpException('Malformed JSON body.', 0, $e);
        }

        if (!isset($data['newPassword']) || !is_string($data['newPassword'])) {
            throw new BadRequestHttpException('Missing or invalid "newPassword" field. Must be a string.');
        }
        $this->newPassword = $data['newPassword'];
    }

    public function newPassword(): string
    {
        return $this->newPassword;
    }
}
