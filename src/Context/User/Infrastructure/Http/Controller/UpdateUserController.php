<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\Update\UpdateUserCommand;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UpdateUserController extends ApiController
{
    public function __invoke(Request $request, string $id): Response
    {
        $data = $request->toArray();
        $this->dispatch(new UpdateUserCommand(
            $id,
            $data['name'],
            $data['lastName'],
            $data['gender'],
            $data['phoneNumber'],
        ));
        return new Response('', Response::HTTP_NO_CONTENT);
    }
    public function exceptions(): array
    {
        return [
            ResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}