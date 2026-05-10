<?php

declare(strict_types=1);

namespace App\Context\Setup\Application\UseCase\FinalizeSetup;

use App\Context\Setup\Application\Service\SetupFinalizationService;
use App\Shared\Application\CommandHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FinalizeSetupCommandHandler implements CommandHandler
{
    public function __construct(
        private SetupFinalizationService $setupFinalization,
    ) {}

    public function __invoke(FinalizeSetupCommand $command): void
    {
        $this->setupFinalization->finalizeOrFail();
    }
}
