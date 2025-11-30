<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\GetSlipDetails;

use App\Context\Slip\Domain\SlipRepository;
use App\Context\Slip\Domain\ValueObject\SlipId;
use App\Shared\Application\QueryHandler;

final readonly class GetSlipDetailsQueryHandler implements QueryHandler
{
    public function __construct(private SlipRepository $repository) {}

    public function __invoke(GetSlipDetailsQuery $query): array
    {
        $slipId = new SlipId($query->slipId);
        $slip = $this->repository->findOneByIdOrFail($slipId);

        $residentUnit = $slip->residentUnit();

        return [
            'id' => $slip->id(),
            'status' => $slip->getStatus(),
            'amount' => $slip->amount(),
            'dueDate' => $slip->dueDate()->format('Y-m-d'),
            'description' => $slip->description(),
            'createdAt' => $slip->createdAt()->format('Y-m-d H:i:s'),
            'residentUnit' => [
                'id' => $residentUnit->id(),
                'unit' => $residentUnit->unit(),
                'idealFraction' => $residentUnit->idealFraction(),
            ],
        ];
    }
}
