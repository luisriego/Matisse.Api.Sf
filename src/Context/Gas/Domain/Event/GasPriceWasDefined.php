<?php

declare(strict_types=1);

namespace App\Context\Gas\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use DateMalformedStringException;
use DateTimeImmutable;
use RuntimeException;
use Symfony\Component\Uid\Uuid;

use function is_numeric;
use function round;

final readonly class GasPriceWasDefined extends DomainEvent
{
    /**
     * @throws DateMalformedStringException
     */
    public function __construct(
        string $id,
        public int $pricePerM3InCents,
        ?string $eventId = null,
        ?string $occurredOn = null,
    ) {
        parent::__construct(
            $id,
            $eventId ?? Uuid::v4()->toRfc4122(),
            $occurredOn ? new DateTimeImmutable($occurredOn) : new DateTimeImmutable(),
        );
    }

    public static function eventName(): string
    {
        return 'gas.price.was.defined';
    }

    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn,
    ): self {
        $cents = self::extractPricePerM3InCentsFromPayload($body);

        return new self(
            $aggregateId,
            $cents,
            $eventId,
            $occurredOn,
        );
    }

    public function toPrimitives(): array
    {
        return [
            'pricePerM3InCents' => $this->pricePerM3InCents,
        ];
    }

    /**
     * @param array<string, mixed> $body
     */
    private static function extractPricePerM3InCentsFromPayload(array $body): int
    {
        if (isset($body['pricePerM3InCents']) && is_numeric($body['pricePerM3InCents'])) {
            return (int) $body['pricePerM3InCents'];
        }

        // Legacy: pricePerM3 was stored as Reals per m³ (float)
        if (isset($body['pricePerM3']) && is_numeric($body['pricePerM3'])) {
            return (int) round((float) $body['pricePerM3'] * 100);
        }

        throw new RuntimeException('GasPriceWasDefined payload must contain pricePerM3InCents or legacy pricePerM3.');
    }
}
