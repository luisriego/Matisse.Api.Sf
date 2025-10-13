<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

use function sprintf;

class ResourceAlreadyExistException extends HttpException
{
    public function __construct(string $message)
    {
        parent::__construct('409', $message);
    }

    public static function with(string $resource, string $id): self
    {
        return new self(sprintf('%s with id "%s" already exists', $resource, $id));
    }
}
