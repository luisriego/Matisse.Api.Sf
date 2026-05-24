<?php

declare(strict_types=1);

namespace App\Context\Setup\Application\UseCase\GetSetupStatus;

use App\Context\Setup\Application\Service\SetupStatusChecker;
use App\Shared\Application\QueryHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetSetupStatusQueryHandler implements QueryHandler
{
    public function __construct(
        private SetupStatusChecker $checker,
    ) {}

    public function __invoke(GetSetupStatusQuery $query): array
    {
        return $this->checker->status();
    }
}
