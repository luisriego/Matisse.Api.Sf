<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\UseCase\AppendRecipients;

use App\Context\ResidentUnit\Domain\ResidentUnitId;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Shared\Application\CommandHandler;

final readonly class AppendRecipientsCommandHandler implements CommandHandler
{
    public function __construct(private ResidentUnitRepository $repository) {}

    public function __invoke(AppendRecipientsCommand $command): void
    {
        $id = new ResidentUnitId($command->id);
        $residentUnit = $this->repository->findOneByIdOrFail($id->value());

        $residentUnit->appendRecipient($command->name, $command->email);

        $this->repository->save($residentUnit);
    }
}
