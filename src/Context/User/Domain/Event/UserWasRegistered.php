<?php

declare(strict_types=1);

namespace App\Context\User\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid as SfUuid;

final readonly class UserWasRegistered extends DomainEvent
{
    public function __construct(
        string $id,
        private string $name,
        private string $email,
        private string $confirmationToken,
        ?string $eventId = null,
        ?DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct(
            $id,
            $eventId ?? SfUuid::v4()->toRfc4122(),
            $occurredOn ?? new DateTimeImmutable(),
        );
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
            $body['confirmationToken'],
            $eventId,
            new DateTimeImmutable($occurredOn),
        );
    }

    public static function eventName(): string
    {
        return 'user.registered';
    }

    public function toPrimitives(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'confirmationToken' => $this->confirmationToken,
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

    public function confirmationToken(): string
    {
        return $this->confirmationToken;
    }
}
