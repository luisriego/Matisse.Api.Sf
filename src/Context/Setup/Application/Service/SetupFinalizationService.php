<?php

declare(strict_types=1);

namespace App\Context\Setup\Application\Service;

use App\Context\Setup\Domain\Event\SetupWasCompleted;
use App\Shared\Application\EventStore;
use App\Shared\Domain\Exception\InvalidDataException;

/**
 * Persists setup.was.completed once; afterwards the app never applies SETUP_REQUIRED again.
 */
final class SetupFinalizationService
{
    public function __construct(
        private readonly EventStore $eventStore,
        private readonly SetupStatusChecker $statusChecker,
    ) {}

    public function isFinalized(): bool
    {
        return $this->statusChecker->isSetupFinalized();
    }

    /**
     * Idempotent: no-op if already finalized or if core steps are not complete yet.
     */
    public function tryFinalizeWhenCoreComplete(): void
    {
        if ($this->statusChecker->isSetupFinalized()) {
            return;
        }

        if (!$this->statusChecker->isComplete()) {
            return;
        }

        $this->eventStore->append(SetupWasCompleted::createForCondominium());
    }

    /**
     * @throws InvalidDataException when not finalized and core setup is incomplete
     */
    public function finalizeOrFail(): void
    {
        if ($this->statusChecker->isSetupFinalized()) {
            return;
        }

        if (!$this->statusChecker->isComplete()) {
            throw new InvalidDataException(
                'Cannot finalize setup: initial balances, gas, and at least one expense must be complete.',
            );
        }

        $this->eventStore->append(SetupWasCompleted::createForCondominium());
    }
}
