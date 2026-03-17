<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Domain\Exception;

use Exception;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;

// Import Response class

final class ResidentUnitAlreadyExistsException extends Exception
{
    public static function create(string $id): self
    {
        return new self(
            sprintf('Resident unit with ID [%s] already exists', $id),
            Response::HTTP_CONFLICT, // Set the HTTP status code as the exception code
        );
    }
}
