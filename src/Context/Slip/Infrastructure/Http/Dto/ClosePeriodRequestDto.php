<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Dto;

use App\Context\Slip\Application\UseCase\ClosePeriod\ClosePeriodCommand;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

use function array_map;
use function explode;
use function is_string;
use function preg_match;

class ClosePeriodRequestDto implements RequestDto
{
    public readonly int $year;
    public readonly int $month;

    public function __construct(Request $request)
    {
        $monthValue = $request->attributes->get('targetMonth');

        if (!is_string($monthValue) || 1 !== preg_match('/^\d{4}-\d{2}$/', $monthValue)) {
            throw new InvalidArgumentException('Invalid targetMonth. Expected YYYY-MM.');
        }

        [$year, $month] = array_map('intval', explode('-', $monthValue));

        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Invalid month. Must be between 01 and 12.');
        }

        $this->year = $year;
        $this->month = $month;
    }

    public function toCommand(): ClosePeriodCommand
    {
        return new ClosePeriodCommand(
            $this->year,
            $this->month,
        );
    }
}
