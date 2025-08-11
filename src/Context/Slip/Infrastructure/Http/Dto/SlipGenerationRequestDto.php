<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Dto;

use App\Context\Slip\Application\UseCase\SlipGenerationCommand;
use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

class SlipGenerationRequestDto implements RequestDto
{
    public string $year;
    public string $month;

    public function __construct(Request $request)
    {
        // Se espera "targetMonth" en formato "YYYY-MM" (p. ej. "2025-07")
        $monthValue = $request->get('targetMonth');
        if (false === preg_match('/^\d{4}-\d{2}$/', $monthValue)) {
            throw new \InvalidArgumentException("Invalid targetMonth: {$monthValue}");
        }

        [$year, $month] = array_map('intval', explode('-', $monthValue));
        $this->year = $year;
        $this->month = $month;
    }

    public function toCommand(): SlipGenerationCommand
    {
        return new SlipGenerationCommand(
            $this->year,
            $this->month,
        );
    }
}