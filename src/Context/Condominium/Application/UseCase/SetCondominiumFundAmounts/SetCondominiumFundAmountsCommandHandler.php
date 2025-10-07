<?php

declare(strict_types=1);

namespace App\Context\Condominium\Application\UseCase\SetCondominiumFundAmounts;

use App\Context\Condominium\Domain\CondominiumConfiguration;
use App\Context\Condominium\Domain\CondominiumConfigurationRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Domain\ValueObject\Uuid;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SetCondominiumFundAmountsCommandHandler implements CommandHandler
{
    public function __construct(
        private CondominiumConfigurationRepository $condominiumConfigurationRepository,
        private EventBus $eventBus,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(SetCondominiumFundAmountsCommand $command): void
    {
        $configurationId = new Uuid($command->condominiumConfigurationId());
        $effectiveDate = new DateTimeImmutable($command->effectiveDate());
        $userId = $command->userId() ? new Uuid($command->userId()) : null;

        try {
            $configuration = $this->condominiumConfigurationRepository->findOrFail();
        } catch (ResourceNotFoundException) {
            // If no configuration exists, create a new one
            $configuration = CondominiumConfiguration::create(
                $configurationId,
                $command->reserveFundAmount(),
                $command->constructionFundAmount(),
                $effectiveDate,
                $userId,
            );
        }

        $configuration->updateAmounts(
            $command->reserveFundAmount(),
            $command->constructionFundAmount(),
            $effectiveDate,
            $userId?->value(),
        );

        $this->condominiumConfigurationRepository->save($configuration, true);
        $this->eventBus->publish(...$configuration->pullDomainEvents());
    }
}
