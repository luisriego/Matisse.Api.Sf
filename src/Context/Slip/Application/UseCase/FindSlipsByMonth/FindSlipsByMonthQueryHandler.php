<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\FindSlipsByMonth;

use App\Context\Slip\Domain\SlipRepository;
use App\Shared\Application\QueryHandler;

final readonly class FindSlipsByMonthQueryHandler implements QueryHandler
{
    public function __construct(private SlipRepository $repository) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(FindSlipsByMonthQuery $query): array
    {
        $slips = $this->repository->findByMonthYear($query->year(), $query->month());

        $out = [];

        foreach ($slips as $slip) {
            $ru = $slip->residentUnit();
            $out[] = [
                'id' => $slip->id(),
                'amount' => $slip->amount(),
                'status' => $slip->getStatus(),
                'dueDate' => $slip->dueDate()->format('Y-m-d'),
                'description' => $slip->description(),
                'createdAt' => $slip->createdAt()->format('Y-m-d H:i:s'),
                'residentUnit' => [
                    'id' => $ru->id(),
                    'unit' => $ru->unit(),
                    'idealFraction' => $ru->idealFraction(),
                ],
            ];
        }

        return $out;
    }
}
