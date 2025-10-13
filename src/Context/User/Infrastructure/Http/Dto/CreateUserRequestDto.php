<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use function is_string;
use function json_decode;

use const JSON_THROW_ON_ERROR;

final class CreateUserRequestDto implements RequestDto
{
    private string $id;
    private string $name;
    private string $email;
    private string $password;

    public function __construct(Request $request)
    {
        try {
            $data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new BadRequestHttpException('Malformed JSON body.', 0, $e);
        }

        if (!isset($data['id']) || !is_string($data['id'])) {
            throw new BadRequestHttpException('Missing or invalid "id" field. Must be a string.');
        }
        $this->id = $data['id'];

        if (!isset($data['name']) || !is_string($data['name'])) {
            throw new BadRequestHttpException('Missing or invalid "name" field. Must be a string.');
        }
        $this->name = $data['name'];

        if (!isset($data['email']) || !is_string($data['email'])) {
            throw new BadRequestHttpException('Missing or invalid "email" field. Must be a string.');
        }
        $this->email = $data['email'];

        if (!isset($data['password']) || !is_string($data['password'])) {
            throw new BadRequestHttpException('Missing or invalid "password" field. Must be a string.');
        }
        $this->password = $data['password'];
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function password(): string
    {
        return $this->password;
    }
}
