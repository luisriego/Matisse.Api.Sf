<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\InviteResident;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\User\Domain\User;
use App\Context\User\Domain\UserRepository;
use App\Context\User\Domain\ValueObject\Email;
use App\Context\User\Domain\ValueObject\UserId;
use App\Context\User\Domain\ValueObject\UserName;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Domain\ValueObject\Uuid;

use function explode;
use function sprintf;
use function str_replace;
use function trim;

final readonly class InviteResidentFromUnitCommandHandler implements CommandHandler
{
    public function __construct(
        private ResidentUnitRepository $residentUnitRepository,
        private UserRepository $userRepository,
    ) {}

    /**
     * @throws ResourceNotFoundException
     */
    public function __invoke(InviteResidentFromUnitCommand $command): void
    {
        $residentUnit = $this->residentUnitRepository->findOneById($command->residentUnitId());

        if (null === $residentUnit) {
            throw new ResourceNotFoundException(
                sprintf('ResidentUnit with ID <%s> not found.', $command->residentUnitId()),
            );
        }

        $email = Email::fromString($command->email());
        $existingUser = $this->userRepository->findByEmail($email->value());

        if (null !== $existingUser) {
            $this->syncExistingResident($existingUser, $residentUnit, $command);

            return;
        }

        $name = null !== $command->name() && '' !== trim($command->name())
            ? UserName::fromString($command->name())
            : UserName::fromString(self::nameFromEmail($email->value()));

        $user = User::invite(
            UserId::fromString((string) Uuid::random()),
            $name,
            $email,
            $residentUnit,
        );

        $this->userRepository->save($user, true);
    }

    private function syncExistingResident(
        User $user,
        ResidentUnit $residentUnit,
        InviteResidentFromUnitCommand $command,
    ): void {
        if ($user->getResidentUnit()?->id() !== $residentUnit->id()) {
            $user->setResidentUnit($residentUnit);
        }

        if (null !== $command->name() && '' !== trim($command->name())) {
            $user->updateName(trim($command->name()));
        }

        $this->userRepository->save($user, true);
    }

    private static function nameFromEmail(string $email): string
    {
        $local = explode('@', $email)[0];

        return str_replace(['.', '_', '-'], ' ', $local);
    }
}
