<?php

declare(strict_types=1);

namespace App\Context\User\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use DateTimeImmutable;

final readonly class CreateUserDomainEvent extends DomainEvent
{
    public function __construct(
        string $id,
        private string $name,
        private string $email,
        private string $password,
        string $eventId,
        string $occurredOn,
    ) {
        parent::__construct($id, $eventId, new DateTimeImmutable($occurredOn));
    }

    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn,
    ): self {
        return new self(
            $aggregateId,
            $body['name'],
            $body['email'],
            $body['password'],
            $eventId,
            $occurredOn,
        );
    }

    public static function eventName(): string
    {
        return 'user.created';
    }

    public function toPrimitives(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
        ];
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
