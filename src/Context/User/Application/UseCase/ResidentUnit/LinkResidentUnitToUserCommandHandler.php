<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\ResidentUnit;

use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\User\Domain\UserRepository;
use InvalidArgumentException;
use RuntimeException;

final readonly class LinkResidentUnitToUserCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private ResidentUnitRepository $residentUnitRepository,
    ) {}

    public function __invoke(LinkResidentUnitToUserCommand $command): void
    {
        $user = $this->userRepository->findOneById($command->userId());

        if (!$user) {
            throw new RuntimeException('User not found');
        }

        $residentUnit = $this->residentUnitRepository->findOneById($command->residentUnitId());

        if (!$residentUnit) {
            throw new InvalidArgumentException('Resident unit not found');
        }

        // Idempotencia: si ya está vinculada la misma unidad, no persistir cambios
        if ($user->getResidentUnit()?->id() === $residentUnit->id()) {
            return;
        }

        $user->setResidentUnit($residentUnit);
        $this->userRepository->save($user, true);
    }
}
