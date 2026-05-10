<?php

declare(strict_types=1);

namespace App\Context\Setup\Application\UseCase\FinalizeSetup;

use App\Shared\Application\Command;

/** Explicitly record setup.was.completed once core steps are satisfied (idempotent). */
final class FinalizeSetupCommand implements Command {}
