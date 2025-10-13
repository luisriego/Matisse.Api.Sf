<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Domain\Service;

use App\Context\Slip\Domain\Exception\GenerationNotAllowedYetException;
use App\Context\Slip\Domain\Exception\PastMonthGenerationRequiresConfirmationException;
use App\Context\Slip\Domain\Exception\RecreationExpiredException;
use App\Context\Slip\Domain\Service\SlipGenerationPolicy;
use App\Context\Slip\Domain\SlipRepository;
use App\Tests\Shared\Infrastructure\FixedClock;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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

    #[Test]
    #[DataProvider('provideSuccessScenarios')]
    public function it_should_allow_generation_on_valid_scenarios(string $today, int $year, int $month, bool $slipsExist, bool $forced): void
    {
        $this->clock->set($today);

        if ($forced) {
            // When forced, the policy should not check for existing slips.
            $this->slipRepository->expects($this->never())->method('existsForDueDateMonth');
        } else {
            $dueDateContext = (new \DateTimeImmutable(sprintf('%d-%d-01', $year, $month)))->modify('+1 month');
            $dueYear = (int)$dueDateContext->format('Y');
            $dueMonth = (int)$dueDateContext->format('m');

            $this->slipRepository->expects($this->once())
                ->method('existsForDueDateMonth')
                ->with($dueYear, $dueMonth)
                ->willReturn($slipsExist);
        }

        // If no exception is thrown, the test passes.
        $this->policy->check($year, $month, $forced);
        $this->addToAssertionCount(1); // Ensures that the test runs and doesn't just do nothing.
    }

    public static function provideSuccessScenarios(): \Generator
    {
        yield 'Forced generation should always be allowed' => ['2024-08-10', 2024, 6, false, true];
        yield 'First time generation, on the 25th' => ['2024-07-25', 2024, 7, false, false];
        yield 'First time generation, after the 25th' => ['2024-07-30', 2024, 7, false, false];
        yield 'First time generation, on the 5th of due month' => ['2024-08-05', 2024, 7, false, false];
        yield 'Recreation, within window' => ['2024-08-02', 2024, 7, true, false];
    }

    #[Test]
    #[DataProvider('provideFailureScenarios')]
    public function it_should_throw_exception_on_invalid_scenarios(string $expectedException, string $today, int $year, int $month, bool $slipsExist, bool $forced): void
    {
        $this->expectException($expectedException);

        $this->clock->set($today);

        // All failure scenarios are not forced, so the repository will always be checked.
        $dueDateContext = (new \DateTimeImmutable(sprintf('%d-%d-01', $year, $month)))->modify('+1 month');
        $dueYear = (int)$dueDateContext->format('Y');
        $dueMonth = (int)$dueDateContext->format('m');

        $this->slipRepository->expects($this->once())
            ->method('existsForDueDateMonth')
            ->with($dueYear, $dueMonth)
            ->willReturn($slipsExist);

        $this->policy->check($year, $month, $forced);
    }

    public static function provideFailureScenarios(): \Generator
    {
        yield 'Generation too early' => [GenerationNotAllowedYetException::class, '2024-07-24', 2024, 7, false, false];
        // The logic in SlipGenerationPolicy throws PastMonthGenerationRequiresConfirmationException when today > lastDayForFirstTime
        yield 'First time generation expired (requires confirmation)' => [PastMonthGenerationRequiresConfirmationException::class, '2024-08-06', 2024, 7, false, false];
        yield 'Recreation expired' => [RecreationExpiredException::class, '2024-08-06', 2024, 7, true, false];
        yield 'Past month generation requires confirmation' => [PastMonthGenerationRequiresConfirmationException::class, '2024-08-10', 2024, 6, false, false];
    }
}
