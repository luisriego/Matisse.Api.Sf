<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Dto;

use App\Context\Slip\Application\UseCase\SlipGeneration\SlipGenerationCommand;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

use function array_map;
use function explode;
use function filter_var;
use function is_string;
use function preg_match;

use const FILTER_VALIDATE_BOOL;

class SlipGenerationRequestDto implements RequestDto
{
    public readonly int $year;
    public readonly int $month;
    public readonly bool $isForced;

    public function __construct(Request $request)
    {
        $monthValue = $request->get('targetMonth');

        if (!is_string($monthValue) || false === preg_match('/^\d{4}-\d{2}$/', $monthValue)) {
            throw new InvalidArgumentException('Invalid targetMonth. Expected YYYY-MM.');
        }

        [$year, $month] = array_map('intval', explode('-', $monthValue));

        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Invalid month. Must be between 01 and 12.');
        }

        $this->year = $year;
        $this->month = $month;

        // Accept both JSON boolean true and string values like "true", "1", "on"
        $this->isForced = filter_var($request->get('force', false), FILTER_VALIDATE_BOOL);
    }

    public function toCommand(): SlipGenerationCommand
    {
        return new SlipGenerationCommand(
            $this->year,
            $this->month,
            $this->isForced,
        );
    }
}
