<?php

namespace App\Tests\Context\Slip\Domain\Service;

use App\Context\Slip\Domain\SlipRepository;
use App\Tests\Shared\Infrastructure\FixedClock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use App\Context\Slip\Domain\Service\SlipGenerationPolicy;
use App\Context\Slip\Domain\Exception\PastMonthGenerationRequiresConfirmationException;
use App\Context\Slip\Domain\Exception\GenerationNotAllowedYetException;
use App\Context\Slip\Domain\Exception\FirstTimeGenerationExpiredException;
use App\Context\Slip\Domain\Exception\RecreationExpiredException;

final class SlipGenerationPolicyTest extends TestCase
{
    private SlipRepository&MockObject $slipRepository;
    private FixedClock $clock;
    private SlipGenerationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->slipRepository = $this->createMock(SlipRepository::class);
        $this->clock = new FixedClock();
        $this->policy = new SlipGenerationPolicy($this->slipRepository, $this->clock);
    }

    public function test_it_should_allow_generation_on_valid_scenarios(): void
    {
        // Cenários manuais (em vez de data providers)
        $scenarios = [
            ['2024-07-25', 2024, 7, false, false], // First time dentro da janela
            ['2024-07-30', 2024, 7, false, false],
            ['2024-08-05', 2024, 7, false, false],
            ['2024-08-02', 2024, 7, true, false],  // Recreation
            ['2024-08-10', 2024, 6, false, true], // Past month, forced
        ];

        foreach ($scenarios as [$today, $year, $month, $slipsExist, $forced]) {
            $this->clock->set($today);
            $this->slipRepository->method('existsForDueDateMonth')->willReturn($slipsExist);

            // Se não lançar exceção, é sucesso
            $this->policy->check($year, $month, (bool)$forced);
        }

        $this->assertTrue(true, 'Nenhuma exceção lançada para cenários válidos.');
    }

    public function test_it_should_throw_exception_on_invalid_scenarios(): void
    {
        $scenarios = [
            // [expectedExceptionClass, today, year, month, slipsExist, forced]
            [GenerationNotAllowedYetException::class, '2024-07-24', 2024, 7, false, false],
            [FirstTimeGenerationExpiredException::class, '2024-08-06', 2024, 7, false, false],
            [RecreationExpiredException::class, '2024-08-06', 2024, 7, true, false],
            [PastMonthGenerationRequiresConfirmationException::class, '2024-08-10', 2024, 6, false, false],
        ];

        foreach ($scenarios as [$expected, $today, $year, $month, $slipsExist, $forced]) {
            $this->expectException($expected);

            $this->clock->set($today);
            $this->slipRepository->method('existsForDueDateMonth')->willReturn($slipsExist);

            $this->policy->check($year, $month, (bool)$forced);
            // Se chegar aqui sem exceção, falhou o teste (a exceção deveria ter sido lançada)
        }
    }
}
