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
use function preg_match;

use const FILTER_VALIDATE_BOOL;

class SlipGenerationRequestDto implements RequestDto
{
    public int $year;
    public int $month;
    public bool $isForced = false;

    public function __construct(Request $request)
    {
        // Is waiting for "targetMonth" in format "YYYY-MM" (ex. "2025-07")
        $monthValue = $request->get('targetMonth');

        if (false === preg_match('/^\d{4}-\d{2}$/', $monthValue)) {
            throw new InvalidArgumentException("Invalid targetMonth: {$monthValue}");
        }

        [$year, $month] = array_map('intval', explode('-', $monthValue));
        $this->year = $year;
        $this->month = $month;

        // Accept both JSON boolean true and string values like "true", "1", "on"
        $this->isForced = filter_var($request->get('force'), FILTER_VALIDATE_BOOL);
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
