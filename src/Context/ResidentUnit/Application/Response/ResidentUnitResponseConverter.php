<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\Response;

use App\Context\ResidentUnit\Domain\ResidentUnit;

final class ResidentUnitResponseConverter
{
    public function __invoke(ResidentUnit $residentUnit): ResidentUnitResponse
    {
        return new ResidentUnitResponse(
            $residentUnit->id(),
            $residentUnit->unit(),
            $residentUnit->idealFraction(),
            $residentUnit->isActive(),
            $residentUnit->createdAt()->format('Y-m-d H:i:s'),
            $residentUnit->updatedAt() ? $residentUnit->updatedAt()->format('Y-m-d H:i:s') : null,
            $residentUnit->notificationRecipients()
        );
    }
}
